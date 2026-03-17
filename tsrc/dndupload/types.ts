import Config from 'core/config';

/** Moodle component name. */
export const PLUGIN_NAME = 'mod_equella';
/** Server endpoint for file uploads. */
export const UPLOAD_ENDPOINT = `${Config.wwwroot}/mod/equella/dndupload.php`;
/** CSS class name to hide elements. */
export const DISPLAY_NONE_CLASS = 'd-none';

/** DOM selectors for the upload form fields and error box. */
export const FORM_SELECTORS = {
    ERROR: '#oeq_validate_error',
    FORM_CONTAINER: '#equella-upload-form-container',
    COPYRIGHT: 'oeq_copyright',
    TITLE: 'oeq_title',
    DESC: 'oeq_desc',
    KEYWORDS: 'oeq_kw',
};

/** Error thrown when the user closes the modal without uploading. */
export class UploadCancelledError extends Error {
    constructor() {
        super('Upload cancelled by user.');
        this.name = 'UploadCancelledError';
    }
}

/** Configuration passed from PHP on initialization. */
export interface InitConfig {
    /** Id of course page where DND is active. */
    courseId: number;
    /** Maximum allowed upload size in bytes. */
    maxBytes: number;
}

/** Runtime state for the current drag-and-drop session. */
export interface DndState extends InitConfig {
    /** The current Moodle session key. */
    sessKey: string;
    /** The target section number (as a string) for the current drop, or `null`. */
    targetSection: string | null;
    /** The database id of the target section, or `null` if unknown. */
    targetSectionId: number | null;
}

/** Metadata collected from the upload modal form. */
export interface UploadData {
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

/** Represents an item from a drag event, distinguishing files from folders. */
export interface DropItem {
    /** The {@link File} object obtained from the drop. */
    file: File;
    /** `true` if the item is a file; `false` if it is a directory. */
    isFile: boolean;
}

/** Localized strings used in the upload modal UI. */
export interface ModalStrings {
    errCopyright: string;
    errTitle: string;
    errDesc: string;
    uploadTitle: string;
    fileInfo: string;
    btnUpload: string;
}

/** Interface for the Moodle core Modal object. */
export interface ModalInstance {
    setRemoveOnClose: (remove: boolean) => void;
    setButtonText: (buttonId: string, text: string) => void;
    show: () => void;
    destroy: () => void;
    getRoot: () => { on: (event: string, cb: (e: Event) => void) => void; 0: HTMLElement };
}
