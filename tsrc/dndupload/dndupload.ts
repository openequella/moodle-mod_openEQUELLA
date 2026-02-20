import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import { get_string } from 'core/str';
import Config from 'core/config';
import { add as addToast } from 'core/toast';
import Templates from 'core/templates';
import { LoadingProcess, processMonitor } from 'core/process_monitor';
import { getCourseEditor } from 'core_courseformat/courseeditor';

// --- Types ---

/**
 * Configuration passed from PHP (`mod_equella_before_footer`) to bootstrap
 * the drag-and-drop upload feature on a course page.
 */
interface InitConfig {
    /** Id of course page where DND is active. */
    courseId: number;
    /** Maximum allowed upload size in bytes. */
    maxBytes: number;
}

/**
 * Runtime state for the DND upload lifecycle.
 * Extends {@link InitConfig} with session and per-drop transient data.
 */
interface DndState extends InitConfig {
    /** The current Moodle session key. */
    sessKey: string;
    /** The file currently being processed, or `null`. */
    activeFile: File | null;
    /** The target section number (as a string) for the current drop, or `null`. */
    targetSection: string | null;
    /** The database id of the target section, or `null` if unknown. */
    targetSectionId: number | null;
}

/** Validated form data ready to be sent to the upload endpoint. */
interface UploadData {
    /** The file to upload. */
    file: File;
    /** The course section number the resource will be added to. */
    section: string;
    /** The selected copyright option value. */
    copyright: string;
    /** The user-provided title for the resource (min 6 chars). */
    title: string;
    /** The user-provided description for the resource. */
    desc: string;
    /** Comma-separated keywords / tags for the resource. */
    keywords: string;
}

/** Resolved identifiers for a course section element in the DOM. */
interface SectionData {
    /** The section number extracted from the element, or `null`. */
    number: string | null;
    /** The database id of the section, or `null`. */
    id: number | null;
}

/** Represents a single item extracted from a {@link DataTransfer} during a drop event. */
interface DropItem {
    /** The {@link File} object obtained from the drop. */
    file: File;
    /** `true` if the item is a file; `false` if it is a directory. */
    isFile: boolean;
}

/** Identifies if a section is in the sidebar, main content, or neither. */
type SectionType = 'sidebar' | 'main' | null;

// --- Constants ---

const SELECTORS = {
    MAIN_SECTION: '.course-content li.section',
    SIDEBAR_SECTION: '#courseindex-content .courseindex-section',
    OVERLAY_PREVIEW: '.overlay-preview',
    FORM: {
        ERROR: '#eq_validate_error',
        FORM_CONTAINER: '#equella-upload-form-container',
        COPYRIGHT: 'eq_copyright',
        TITLE: 'eq_title',
        DESC: 'eq_desc',
        KEYWORDS: 'eq_kw',
    },
}

const CLASSES = {
    HIDE: 'd-none',
    SIDEBAR_BORDER: 'overlay-preview-borders',
    DRAG_OVER: 'dragover',
}

const UPLOAD_ENDPOINT = `${Config.wwwroot}/mod/equella/dndupload.php`;

// --- Entry Point ---

/**
 * Called from PHP to initialise DND upload. See `lib.php`.
 * @param config Initial configuration passed from PHP.
 */
export const init = (config: InitConfig): void => {
    const state: DndState = {
        ...config,
        sessKey: Config.sesskey,
        activeFile: null,
        targetSection: null,
        targetSectionId: null,
    };

    bindEvents(state);
};

// --- Event Binding ---

/** Registers global `dragover`, `dragenter`, `dragleave`, and `drop` listeners on `document`. */
const bindEvents = (state: DndState): void => {
    document.addEventListener('dragover', (e: DragEvent) => {
        if (isFilesDrag(e)) e.preventDefault();
    }, true);

    document.addEventListener('dragenter', (e: DragEvent) => {
        if (!isFilesDrag(e)) return;
        e.preventDefault();
        const section = findClosestSection(e.target as HTMLElement);
        if (section) showSectionOverlay(section);
    }, true);

    document.addEventListener('dragleave', (e: DragEvent) => {
        if (!isFilesDrag(e)) return;
        const target = e.target as HTMLElement;
        if (!target || target.nodeType !== Node.ELEMENT_NODE) return;

        const section = findClosestSection(target);
        if (!section) return;

        // Ignore dragleave if the mouse is just moving between child elements of the same section.
        if (e.relatedTarget && section.contains(e.relatedTarget as Node)) return;
        hideSectionOverlay(section);
    }, true);

    document.addEventListener('drop', async (e: DragEvent) => {
        if (!isFilesDrag(e)) return;
        e.preventDefault();
        e.stopPropagation();
        resetVisuals();
        await handleDrop(e, state);
    }, true);
};

/**
 * Processes a drop event: resolves the target section, iterates dropped items,
 * rejects folders, and delegates each file to {@link handleFileUpload}.
 */
const handleDrop = async (e: DragEvent, state: DndState): Promise<void> => {
    const dt = e.dataTransfer;
    if (!dt || dt.items.length === 0) return;

    const targetElement = findClosestSection(e.target as HTMLElement);
    if (!targetElement) return;

    const sectionData = resolveSectionData(targetElement);
    if (sectionData.number === null) return;

    state.targetSection = sectionData.number;
    state.targetSectionId = sectionData.id;

    for (const { file, isFile } of extractDropItems(dt)) {
        if (!isFile) {
            await showErrorToast(`Folders cannot be uploaded directly. Please zip "${file.name}" and try again.`);
            continue;
        }
        await handleFileUpload(file, state);
    }
};

/**
 * Returns `true` if the drag event contains files.
 * This prevents the uploader from activating when users drag text, links, or internal page elements.
 */
const isFilesDrag = (e: DragEvent): boolean =>
    !!e.dataTransfer?.types.includes('Files');

/**
 * Converts raw {@link DataTransfer} items into an array of {@link DropItem}s,
 * tagging each with whether it is a file or a directory.
 */
const extractDropItems = (dt: DataTransfer): Array<DropItem> => {
    const items: Array<DropItem> = [];
    for (const item of [...dt.items]) {
        const entry = item.webkitGetAsEntry();
        const file = item.getAsFile();
        if (entry && file) items.push({ file, isFile: entry.isFile });
    }
    return items;
};

// --- Section Overlay ---

/** Tracks all elements that currently carry a visual DND highlight. */
const highlightedSections = new Set<Element>();

/** Determines whether a section element belongs to the sidebar or main content area. */
const getSectionType = (section: Element): SectionType => {
    if (section.matches(SELECTORS.SIDEBAR_SECTION)) return 'sidebar';
    if (section.matches(SELECTORS.MAIN_SECTION)) return 'main';
    return null;
};

/** Walks up the DOM from `target` to find the closest course-section element. */
const findClosestSection = (target: HTMLElement): HTMLElement | null => {
    const { MAIN_SECTION, SIDEBAR_SECTION } = SELECTORS;
    return target.closest(`${MAIN_SECTION}, ${SIDEBAR_SECTION}`);
};

/** Extracts the section number and database id from a section element's data attributes. */
const resolveSectionData = (target: HTMLElement): SectionData => ({
    number:
        target.getAttribute('data-number') ??
        target.getAttribute('data-section') ??
        target.id?.replace('section-', '') ??
        null,
    id: Number(target.getAttribute('data-id')) || null,
});

/** Shows the drag-over visual overlay on the given section element. */
const showSectionOverlay = (section: Element): void => {
    const type = getSectionType(section);
    if (type === 'sidebar') {
        section.classList.add(CLASSES.SIDEBAR_BORDER);
    } else if (type === 'main') {
        section.querySelector(SELECTORS.OVERLAY_PREVIEW)?.classList.remove(CLASSES.HIDE);
    }
    highlightedSections.add(section);
};

/** Hides the drag-over visual overlay on the given section element. */
const hideSectionOverlay = (section: Element): void => {
    const type = getSectionType(section);
    section.classList.remove(CLASSES.DRAG_OVER);
    if (type === 'sidebar') {
        section.classList.remove(CLASSES.SIDEBAR_BORDER);
    } else if (type === 'main') {
        section.querySelector(SELECTORS.OVERLAY_PREVIEW)?.classList.add(CLASSES.HIDE);
    }
};

/** Removes all active drag-over highlights across every tracked section. */
const resetVisuals = (): void => {
    highlightedSections.forEach(hideSectionOverlay);
    highlightedSections.clear();
};

// --- Modal ---

/**
 * Opens the upload modal, waits for the user to submit or cancel, and returns
 * the validated {@link UploadData} on success or `null` on cancellation.
 */
const launchUploadModal = async (state: DndState): Promise<UploadData | null> => {
    if (!state.activeFile) return null;

    const modal = await renderModal(state);

    return new Promise<UploadData | null>((resolve) => {
        let finalData: UploadData | null = null;

        modal.getRoot().on(ModalEvents.save, (e: Event) => {
            e.preventDefault();
            const root = modal.getRoot()[0] as HTMLElement;
            try {
                finalData = validateForm(root, state);
                modal.destroy();
            } catch (err: any) {
                showFormError(root, err.message);
            }
        });
        
        modal.getRoot().on(ModalEvents.hidden, () => resolve(finalData));
    });
};

/**
 * Renders the upload modal dialog using the `mod_equella/dnd_modal` Mustache template.
 * @returns The created {@link ModalSaveCancel} instance.
 */
const renderModal = async (state: DndState) => {
    const { name, size } = state.activeFile!;

    const [formHtml, btnText] = await Promise.all([
        Templates.render('mod_equella/dnd_modal', { fileName: name, fileSize: formatBytes(size) }),
        get_string('upload', 'core'),
    ]);

    const modal = await ModalSaveCancel.create({
        title: 'Add to openEQUELLA',
        body: formHtml,
        large: true,
    });

    modal.setRemoveOnClose(true);
    modal.setButtonText('save', btnText);
    modal.show();
    return modal;
};

/**
 * Reads the copyright, title, description, and keywords values from the modal form.
 * @param root The modal's root DOM element.
 */
const extractFormValues = (root: HTMLElement) => {
    const { FORM_CONTAINER, COPYRIGHT, TITLE, DESC, KEYWORDS } = SELECTORS.FORM;
    const form = root.querySelector(FORM_CONTAINER) as HTMLFormElement;
    const formData = new FormData(form);
    const getField = (name: string) => (formData.get(name) as string ?? '').trim();

    return {
        copyright: formData.get(COPYRIGHT) as string | null,
        title: getField(TITLE),
        desc: getField(DESC),
        keywords: getField(KEYWORDS),
    };
};

/**
 * Throws a descriptive error if any required form field is missing or too short.
 * @throws {Error} When validation fails.
 */
const assertFormValid = (copyright: string | null, title: string, desc: string): void => {
    if (!copyright) throw new Error('Please select a copyright option.');
    if (title.length < 6) throw new Error('Title must be at least 6 characters.');
    if (desc.length < 2) throw new Error('Description is required.');
};

/**
 * Validates the modal form and assembles an {@link UploadData} object.
 * @throws {Error} When the form is invalid or internal state is missing.
 */
const validateForm = (root: HTMLElement, state: DndState): UploadData => {
    if (!state.activeFile || !state.targetSection) {
        throw new Error('Internal Error: Missing file or section.');
    }

    const { copyright, title, desc, keywords } = extractFormValues(root);
    assertFormValid(copyright, title, desc);

    return { file: state.activeFile, section: state.targetSection, copyright: copyright!, title, desc, keywords };
};

/** Displays validation error message inside the modal form. */
const showFormError = (root: HTMLElement, message: string): void => {
    const errorBox = root.querySelector(SELECTORS.FORM.ERROR) as HTMLElement | null;
    if (errorBox) {
        errorBox.classList.remove('d-none');
        errorBox.innerHTML = message;
    }
};

// --- Uploader ---

/**
 * Handles a single file upload: validates size, opens the modal,
 * performs the XHR upload, and refreshes the course section state.
 */
const handleFileUpload = async (file: File, state: DndState): Promise<void> => {
    if (state.maxBytes > 0 && file.size > state.maxBytes) {
        await showErrorToast(`"${file.name}" exceeds the upload limit (${formatBytes(file.size)} / ${formatBytes(state.maxBytes)}).`);
        return;
    }

    state.activeFile = file;

    const data = await launchUploadModal(state);
    if (!data) return;

    const process = processMonitor.addLoadingProcess({ name: data.file.name });

    try {
        await performUpload(data, state, process);
        await refreshSectionState(state.courseId, state.targetSectionId);
    } catch (e) {
        process.setError(e instanceof Error ? e.message : String(e));
    } finally {
        setTimeout(() => process.remove(), 2000);
    }
};

/** Builds the multipart {@link FormData} payload for the upload XHR. */
const buildFormData = (data: UploadData, state: DndState): FormData => {
    const fd = new FormData();
    fd.append('repo_upload_file', data.file);
    fd.append('sesskey', state.sessKey);
    fd.append('course', state.courseId.toString());
    fd.append('section', data.section);
    fd.append('module', 'equella');
    fd.append('type', 'Files');
    fd.append('dndcopyright', data.copyright);
    fd.append('dndtitle', data.title);
    fd.append('dnddesc', data.desc);
    fd.append('dndkw', data.keywords);
    return fd;
};

/**
 * Parses the XHR response.
 * @throws {Error} On non-200 status or a non-zero `error` field.
 */
const parseUploadResponse = (xhr: XMLHttpRequest): void => {
    if (xhr.status !== 200) throw new Error(`HTTP Error: ${xhr.status}`);
    const result = JSON.parse(xhr.responseText);
    if (result?.error !== 0) throw new Error(result?.error || 'Unknown error');
};

/** Wires up progress, load, and error handlers on the given {@link XMLHttpRequest}. */
const attachXhrHandlers = (
    xhr: XMLHttpRequest,
    process: LoadingProcess,
    resolve: () => void,
    reject: (err: Error) => void,
): void => {
    xhr.upload.onprogress = (e: ProgressEvent) => {
        if (e.lengthComputable) {
            process.setPercentage(Math.round((e.loaded / e.total) * 100));
        }
    };
    xhr.onload = () => {
        try {
            parseUploadResponse(xhr);
            process.setPercentage(100);
            process.finish();
            resolve();
        } catch (err) {
            reject(err as Error);
        }
    };
    xhr.onerror = () => reject(new Error('Network Error'));
};

/** Sends the upload XHR and returns a promise that resolves on success. */
const performUpload = (data: UploadData, state: DndState, process: LoadingProcess): Promise<void> =>
    new Promise<void>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', UPLOAD_ENDPOINT, true);
        attachXhrHandlers(xhr, process, resolve, reject);
        xhr.send(buildFormData(data, state));
    });

/**
 * Dispatches a Moodle course-editor state refresh so the new activity
 * appears without a full page reload.
 */
const refreshSectionState = async (courseId: number, sectionDbId: number | null): Promise<void> => {
    const editor = getCourseEditor(courseId);
    if (!editor) return;

    if (sectionDbId) {
        await editor.dispatch('sectionState', [sectionDbId]);
    } else {
        await editor.dispatch('courseState');
    }
};

// --- Utils ---

/**
 * Formats a byte count into a human-readable string (e.g. `"1.5 MB"`).
 * @param bytes The number of bytes.
 * @param decimals The number of decimal places (default `2`).
 */
const formatBytes = (bytes: number, decimals = 2): string => {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = Math.max(0, decimals);
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

/** Displays a danger toast notification with a fixed title. */
const showErrorToast = async (msg: string): Promise<void> => await addToast(msg, { type: 'danger', title: 'Upload Failed' })