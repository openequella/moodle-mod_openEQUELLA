/* eslint-disable @typescript-eslint/no-explicit-any */
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import { get_string } from 'core/str';
import Config from 'core/config';
import Notification from 'core/notification'; // <--- Added for error alerts

/**
 * Initial configuration passed from PHP.
 */
interface InitConfig {
    /**
     * Id of course page where DND is active.
     */
    courseId: number;
    /**
     * Maximum allowed upload size in bytes.
     */
    maxBytes: number;
}

interface DndState extends InitConfig {
    sessKey: string;
    activeFile: File | null;
    targetSection: string | null;
}

interface UploadData {
    file: File;
    section: string;
    copyright: string;
    title: string;
    desc: string;
    keywords: string;
}

/**
 * Function typically called from PHP to initialize DND upload. See `lib.php`.
 * @param config Initial configuration passed from PHP.
 */
export const init = (config: InitConfig): void => {
    const state: DndState = {
        ...config,
        sessKey: Config.sesskey,
        activeFile: null,
        targetSection: null
    };

    buildCustomStyles();
    bindEvents(state);
    console.debug(`EQUELLA Moodle Module DND: Listener ready.`);
};



//  Add custom CSS styles to the head to make sure the DND UI indicators are properly shown/hidden.
const buildCustomStyles = (): void => {
    const style: HTMLStyleElement = document.createElement('style');
    style.textContent = `
        .equella-dnd-hide{
            display: none !important;
        }
    `;
    document.head.appendChild(style);
};

const bindEvents = (state: DndState): void => {
    // Checks if the user is dragging a file.
    document.addEventListener('dragover', (e: DragEvent) => {
        if (e.dataTransfer?.types.includes('Files')) {
            e.preventDefault(); // Prevent browser default handling (e.g., opening file)
            e.stopPropagation(); // Prevent different DND provided by other modules from interfering
        }
    }, true);

    document.addEventListener('dragenter', (e: DragEvent) => {
        const target = e.target as HTMLElement;
        // User can drop a file in two areas:
        // 1. Any topic displayed within the Main course content;
        // 2. Any topic displayed within the Sidebar;
        const TOPIC_IN_MAIN = '.course-content li.section';
        const TOPIC_IN_SIDEBAR = '#courseindex-content .courseindex-section';

        const targetSection = target.closest(`${TOPIC_IN_MAIN}, ${TOPIC_IN_SIDEBAR}`);
        console.log("target section dragenter", targetSection)
        if (targetSection) {
            const previews = targetSection.querySelector(' .overlay-preview')
            previews?.classList.remove('equella-dnd-hide');
        }
    }, true);

    document.addEventListener('dragleave', (e: DragEvent) => {
        const target = e.target as HTMLElement;

        // --- SAFETY GUARD ---
        // 1. Check if target exists
        // 2. Check if it is an ELEMENT_NODE (Type 1) (Ignores 'document' and Text nodes)
        if (!target || target.nodeType !== Node.ELEMENT_NODE) {
            return;
        }
        // --------------------

        const TOPIC_IN_MAIN = '.course-content li.section';
        const TOPIC_IN_SIDEBAR = '#courseindex-content .courseindex-section';

        // Now it is safe to call .closest()
        const section = target.closest(`${TOPIC_IN_MAIN}, ${TOPIC_IN_SIDEBAR}`);

        if (!section) return;

        // ... continue with your relatedTarget logic ...
        if (e.relatedTarget && section.contains(e.relatedTarget as Node)) {
            return;
        }

        const previews = section.querySelectorAll('.overlay-preview');
        previews.forEach(el => el.classList.add('equella-dnd-hide'));

    }, true);

    document.addEventListener('drop', (e) => handleDrop(e, state), true);
};

const updateVisualShields = (activeSection: Element): void => {
    // 1. CLEAR: Find any currently active overlays and turn them OFF
    // (We do this first so we don't accidentally turn off the one we are about to turn on)
    document.querySelectorAll('.equella-dnd-active')
        .forEach(el => el.classList.remove('equella-dnd-active'));

    // 2. HIGHLIGHT: Turn ON the overlays for the new section
    const previews = activeSection.querySelector(' .overlay-preview')
    previews?.classList.remove('equella-dnd-hide');
};

const resetVisuals = (e: DragEvent): void => {
    const target = e.target as HTMLElement;
    const TOPIC_IN_MAIN = '.course-content li.section';
    const TOPIC_IN_SIDEBAR = '#courseindex-content .courseindex-section';

    const targetSection = target.closest(`${TOPIC_IN_MAIN}, ${TOPIC_IN_SIDEBAR}`);
// Just find the active ones and remove the class
    targetSection!.classList.remove("dragover")
    const preview = targetSection!.querySelector('.overlay-preview');
    console.log(`EQUELLA DND: Resetting ${preview} visuals for section.`);
    // Hide on drop
    preview?.classList.add('equella-dnd-hide')
};

const handleDrop = async (dragEvent: DragEvent, state: DndState): Promise<void> => {
    // Must stop propagation to avoid Moodle's own DND handling.
    dragEvent.preventDefault();
    dragEvent.stopPropagation();

    if (!dragEvent.dataTransfer || dragEvent.dataTransfer.files.length === 0) return;

    const targetElement = (dragEvent.target as HTMLElement).closest('li.section, .courseindex-section, .courseindex-item') as HTMLElement;
    if (!targetElement) return;

    const file = dragEvent.dataTransfer.files[0];


    resetVisuals(dragEvent);

    // 4. CHECK FILE SIZE (Immediate Fail)
    // maxbytes might be 0 or -1 (unlimited) in some rare configs, usually it is positive.
    if (state.maxBytes > 0 && file.size > state.maxBytes) {
        const errTitle = await get_string('error', 'core');
        // We manually construct the message: "File is too large (X). Max allowed is Y."
        const msg = `The file "${file.name}" is too large.<br>
                     <strong>Size:</strong> ${formatBytes(file.size)}<br>
                     <strong>Limit:</strong> ${formatBytes(state.maxBytes)}`;

        Notification.alert(errTitle, msg);
        return;
    }

    // Capture State
    state.activeFile = file;
    state.targetSection = resolveSectionNumber(targetElement);

    if (state.targetSection === null) {
        console.warn("EQUELLA: Could not determine section number.");
        return;
    }

    console.log(`📂 Dropped: ${state.activeFile.name} -> Section ${state.targetSection}`);
    launchUploadModal(state);
};

const resolveSectionNumber = (target: HTMLElement): string | null => {
    // Sidebar
    if (target.matches('.courseindex-item') || target.closest('.courseindex-item')) {
        const sidebarSection = target.closest('.courseindex-section');
        return sidebarSection?.getAttribute('data-number') || null;
    }
    if (target.matches('.courseindex-section')) {
        return target.getAttribute('data-number');
    }

    // Main Content
    const mainSection = target.closest('li.section');
    if (mainSection) {
        return mainSection.getAttribute('data-section') ||
            mainSection.getAttribute('data-number') ||
            (mainSection.getAttribute('id')?.replace('section-', '') || null);
    }
    return null;
};

// --- UI: Modal & Form ---

const launchUploadModal = async (state: DndState): Promise<void> => {
    if (!state.activeFile) return;

    const formHtml = renderFormHtml(state.activeFile);

    const modal = await ModalSaveCancel.create({
        title: 'Add to EQUELLA',
        body: formHtml,
        large: true,
    });

    const btnText = await get_string('upload', 'core');
    modal.setButtonText('save', btnText);
    modal.show();

    modal.getRoot().on(ModalEvents.save, (e: Event) => {
        e.preventDefault();
        handleFormSubmit(modal, state);
    });
};

const renderFormHtml = (file: File): string => {
    return `
        <div class="equella-upload-form">
            <div class="alert alert-info">
                <strong>File:</strong> ${file.name} (${formatBytes(file.size)})
            </div>
            <div class="form-group">
                <label><strong>Does this include copyright content?*</strong></label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="eq_copyright" id="cp_yes" value="Yes">
                        <label class="form-check-label" for="cp_yes">Yes</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="eq_copyright" id="cp_no" value="No">
                        <label class="form-check-label" for="cp_no">No</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="eq_title"><strong>Title*</strong></label>
                <input type="text" class="form-control" id="eq_title" value="${file.name}">
                <small class="form-text text-muted">Min 6 characters.</small>
            </div>
            <div class="form-group">
                <label for="eq_desc"><strong>Description*</strong></label>
                <textarea class="form-control" id="eq_desc" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="eq_kw"><strong>Keywords</strong></label>
                <input type="text" class="form-control" id="eq_kw" placeholder="Comma separated">
            </div>
            <div id="eq_upload_error" class="alert alert-danger" style="display:none;"></div>
        </div>
    `;
};

// --- Form Validation & Network ---

const handleFormSubmit = (modal: any, state: DndState): void => {
    const root = modal.getRoot()[0] as HTMLElement;
    const errorBox = root.querySelector('#eq_upload_error') as HTMLElement;

    try {
        const data = validateForm(root, state);
        performUpload(data, modal, errorBox, state);
    } catch (err: any) {
        errorBox.innerHTML = err.message;
        errorBox.style.display = 'block';
    }
};

const validateForm = (root: HTMLElement, state: DndState): UploadData => {
    if (!state.activeFile || !state.targetSection) throw new Error("Internal Error: Missing file or section.");

    const copyrightEl = root.querySelector('input[name="eq_copyright"]:checked') as HTMLInputElement;
    const title = (root.querySelector('#eq_title') as HTMLInputElement).value.trim();
    const desc = (root.querySelector('#eq_desc') as HTMLTextAreaElement).value.trim();
    const kw = (root.querySelector('#eq_kw') as HTMLInputElement).value.trim();

    if (!copyrightEl) throw new Error("Please select a copyright option.");
    if (title.length < 6) throw new Error("Title must be at least 6 characters.");
    if (desc.length < 2) throw new Error("Description is required.");

    return {
        file: state.activeFile,
        section: state.targetSection,
        copyright: copyrightEl.value,
        title,
        desc,
        keywords: kw
    };
};

const performUpload = (data: UploadData, modal: any, errorBox: HTMLElement, state: DndState): void => {
    modal.setBody('<div class="text-center p-4"><h3>Uploading...</h3></div>');
    modal.setFooter('');

    const formData = new FormData();
    formData.append('repo_upload_file', data.file);
    formData.append('sesskey', state.sessKey);
    formData.append('course', state.courseId.toString());
    formData.append('section', data.section);
    formData.append('module', 'equella');
    formData.append('type', 'Files');
    formData.append('dndcopyright', data.copyright);
    formData.append('dndtitle', data.title);
    formData.append('dnddesc', data.desc);
    formData.append('dndkw', data.keywords);

    const xhr = new XMLHttpRequest();
    xhr.open("POST", `${Config.wwwroot}/mod/equella/dndupload.php`, true);

    xhr.onload = () => {
        if (xhr.status === 200) {
            try {
                const result = JSON.parse(xhr.responseText);
                if (result?.error == 0) {
                    window.location.reload();
                } else {
                    handleUploadError(result.error || "Unknown error", modal);
                }
            } catch (e) {
                handleUploadError("Invalid Server Response", modal);
            }
        } else {
            handleUploadError(`HTTP Error: ${xhr.status}`, modal);
        }
    };
    xhr.send(formData);
};

const handleUploadError = (msg: string, modal: any): void => {
    modal.setBody(`<div class="alert alert-danger"><strong>Upload Failed:</strong> ${msg}</div>`);
};

/**
 * Helper: Format Bytes to human readable string (KB, MB)
 */
const formatBytes = (bytes: number, decimals = 2): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};