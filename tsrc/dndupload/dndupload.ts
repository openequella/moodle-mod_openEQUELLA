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

/** Represents a single item extracted from a {@link DataTransfer} during a drop event. */
interface DropItem {
    /** The {@link File} object obtained from the drop. */
    file: File;
    /** `true` if the item is a file; `false` if it is a directory. */
    isFile: boolean;
}

/** Collection of localized strings used in the DND Modal. */
interface ModalStrings {
    errCopyright: string;
    errTitle: string;
    errDesc: string;
    errInternal: string;
    uploadTitle: string;
    fileInfo: string;
    btnUpload: string;
}

// --- Constants ---

const FORM_SELECTORS = {
    ERROR: '#eq_validate_error',
    FORM_CONTAINER: '#equella-upload-form-container',
    COPYRIGHT: 'eq_copyright',
    TITLE: 'eq_title',
    DESC: 'eq_desc',
    KEYWORDS: 'eq_kw',
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

    const dropTransferRef = captureDropTransfers();
    registerUploadHandler(state, dropTransferRef);
};

// --- Event Interception ---

/**
 * Attaches a `drop` listener that stores the latest {@link DataTransfer} so directory detection can be performed later.
 * @returns A ref object whose `.value` holds the most recent drop's {@link DataTransfer}.
 */
const captureDropTransfers = (): { value: DataTransfer | null } => {
    const ref: { value: DataTransfer | null } = { value: null };
    document.addEventListener('drop', (e: DragEvent) => {
        if (isFilesDrag(e)) {
            ref.value = e.dataTransfer;
        }
    }, true);
    return ref;
};

/**
 * Overrides the Moodle course editor's native `uploadFiles` method so
 * dropped files are routed through the openEQUELLA upload modal instead.
 */
const registerUploadHandler = (
    state: DndState,
    dropTransferRef: { value: DataTransfer | null },
): void => {
    const courseEditor = getCourseEditor(state.courseId);

    if (!courseEditor) {
        console.warn('EQUELLA: Course editor not found. DND custom interception failed.');
        return;
    }

    courseEditor.uploadFiles = async (sectionId: number, sectionNum: number, files: File[]) => {
        state.targetSection = sectionNum.toString();
        state.targetSectionId = sectionId;

        const validFiles = await filterValidFiles(files, dropTransferRef);
        dropTransferRef.value = null;

        for (const file of validFiles) {
            await handleFileUpload(file, state);
        }
    };
};

// --- Drop Processing ---

/**
 * Filters out directories from the dropped items.
 * Falls back to the raw `files` array when {@link DataTransfer} is unavailable.
 */
const filterValidFiles = async (
    files: File[],
    dropTransferRef: { value: DataTransfer | null },
): Promise<File[]> => {
    if (!dropTransferRef.value) return files;

    const validFiles: File[] = [];
    const dropItems = extractDropItems(dropTransferRef.value);

    for (const item of dropItems) {
        if (item.isFile) {
            validFiles.push(item.file);
        } else {
            const msg = await getModEquellaString('dnd.err.folder', item.file.name);
            await showErrorToast(msg);
        }
    }

    return validFiles;
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

// --- Modal ---

/**
 * Loads all localized strings required for the modal and validation.
 */
const loadModalStrings = async (file: File): Promise<ModalStrings> => {
    const keyPrefix = 'dnd.modal.';
    const {name, size} = file;
    
    const [errCopyright, errTitle, errDesc, errInternal, uploadTitle, fileInfo, btnUpload] = await Promise.all([
        getModEquellaString(`${keyPrefix}err.copyright`),
        getModEquellaString(`${keyPrefix}err.title`),
        getModEquellaString(`${keyPrefix}err.desc`),
        getModEquellaString(`${keyPrefix}err.internal`),
        getModEquellaString(`${keyPrefix}title.upload`),
        getModEquellaString(`${keyPrefix}fileinfo`, {name, size: formatBytes(size)}),
        get_string('upload', 'core'),
    ]);

    return { errCopyright, errTitle, errDesc, errInternal, uploadTitle, fileInfo, btnUpload };
};

/**
 * Opens the upload modal, waits for the user to submit or cancel, and returns
 * the validated {@link UploadData} on success or `null` on cancellation.
 */
const launchUploadModal = async (state: DndState): Promise<UploadData | null> => {
    if (!state.activeFile) return null;

    const strings = await loadModalStrings(state.activeFile);
    const modal = await renderModal(strings);

    return new Promise<UploadData | null>((resolve) => {
        let finalData: UploadData | null = null;

        modal.getRoot().on(ModalEvents.save, (e: Event) => {
            e.preventDefault();
            const root = modal.getRoot()[0] as HTMLElement;
            try {
                finalData = validateForm(root, state, strings);
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
const renderModal = async (strings: ModalStrings) => {
    const formHtml = await Templates.render('mod_equella/dnd_modal', {
        fileInfo: strings.fileInfo
    });

    const modal = await ModalSaveCancel.create({
        title: strings.uploadTitle,
        body: formHtml,
        large: true,
    });

    modal.setRemoveOnClose(true);
    modal.setButtonText('save', strings.btnUpload);
    modal.show();
    return modal;
};

/**
 * Reads the copyright, title, description, and keywords values from the modal form.
 * @param root The modal's root DOM element.
 */
const extractFormValues = (root: HTMLElement) => {
    const { FORM_CONTAINER, COPYRIGHT, TITLE, DESC, KEYWORDS } = FORM_SELECTORS;
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
const assertFormValid = (
    copyright: string | null,
    title: string,
    desc: string,
    strings: ModalStrings
): void => {
    if (!copyright) throw new Error(strings.errCopyright);
    if (title.length < 6) throw new Error(strings.errTitle);
    if (desc.length < 2) throw new Error(strings.errDesc);
};

/**
 * Validates the modal form and assembles an {@link UploadData} object.
 * @throws {Error} When the form is invalid or internal state is missing.
 */
const validateForm = (root: HTMLElement, state: DndState, strings: ModalStrings): UploadData => {
    if (!state.activeFile || !state.targetSection) {
        throw new Error(strings.errInternal);
    }

    const { copyright, title, desc, keywords } = extractFormValues(root);
    assertFormValid(copyright, title, desc, strings);

    return { file: state.activeFile, section: state.targetSection, copyright: copyright!, title, desc, keywords };
};

/** Displays validation error message inside the modal form. */
const showFormError = (root: HTMLElement, message: string): void => {
    const errorBox = root.querySelector(FORM_SELECTORS.ERROR) as HTMLElement | null;
    if (errorBox) {
        errorBox.classList.remove('d-none');
        errorBox.textContent = message;
    }
};

// --- Uploader ---

/**
 * Handles a single file upload: validates size, opens the modal,
 * performs the XHR upload, and refreshes the course section state.
 */
const handleFileUpload = async (file: File, state: DndState): Promise<void> => {
    if (state.maxBytes > 0 && file.size > state.maxBytes) {
        const msg = await get_string('dndmaxbytes', 'core_error', {size: formatBytes(state.maxBytes)});
        await showErrorToast(msg);
        return;
    }

    state.activeFile = file;

    const data = await launchUploadModal(state);
    if (!data) return;

    const process = processMonitor.addLoadingProcess({ name: data.file.name });

    try {
        await performUpload(data, state, process);
        await refreshSectionState(state);
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
const parseUploadResponse = async (xhr: XMLHttpRequest): Promise<void> => {
    if (xhr.status !== 200) {
        throw new Error(await getModEquellaString('dnd.err.http', xhr.status.toString()));
    }
    const result = JSON.parse(xhr.responseText);
    if (result?.error !== 0) {
        throw new Error(result?.error || await get_string('dndupload', 'core_error'));
    }
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
    xhr.onload = async () => {
        try {
            await parseUploadResponse(xhr);
            process.setPercentage(100);
            process.finish();
            resolve();
        } catch (err) {
            reject(err as Error);
        }
    };
    xhr.onerror = async () => reject(new Error(await getModEquellaString('dnd.err.network')));
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
const refreshSectionState = async (state: DndState): Promise<void> => {
    const {courseId, targetSectionId} = state;
    const editor = getCourseEditor(courseId);
    if (!editor) return;

    if (targetSectionId) {
        await editor.dispatch('sectionState', [targetSectionId]);
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
const showErrorToast = async (msg: string): Promise<void> => await addToast(msg, {
    type: 'danger',
    title: await getModEquellaString('dnd.err.title.uploadfailed')
});

/**
 * Helper to fetch a localized string for the mod_equella component.
 * @param key The string key.
 * @param param Optional parameters for the string.
 */
const getModEquellaString = (key: string, param?: string | object): Promise<string> =>
    get_string(key, 'mod_equella', param);