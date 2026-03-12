import Config from 'core/config';

export const PLUGIN_NAME = 'mod_equella';
export const UPLOAD_ENDPOINT = `${Config.wwwroot}/mod/equella/dndupload.php`;
export const DISPLAY_NONE_CLASS = 'd-none';

export const FORM_SELECTORS = {
    ERROR: '#oeq_validate_error',
    FORM_CONTAINER: '#equella-upload-form-container',
    COPYRIGHT: 'oeq_copyright',
    TITLE: 'oeq_title',
    DESC: 'oeq_desc',
    KEYWORDS: 'oeq_kw',
};

export class UploadCancelledError extends Error {
    constructor() {
        super('Upload cancelled by user.');
        this.name = 'UploadCancelledError';
    }
}

export interface InitConfig {
    /** Id of course page where DND is active. */
    courseId: number;
    /** Maximum allowed upload size in bytes. */
    maxBytes: number;
}

export interface DndState extends InitConfig {
    /** The current Moodle session key. */
    sessKey: string;
    /** The target section number (as a string) for the current drop, or `null`. */
    targetSection: string | null;
    /** The database id of the target section, or `null` if unknown. */
    targetSectionId: number | null;
}

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

export interface DropItem {
    /** The {@link File} object obtained from the drop. */
    file: File;
    /** `true` if the item is a file; `false` if it is a directory. */
    isFile: boolean;
}

export interface ModalStrings {
    errCopyright: string;
    errTitle: string;
    errDesc: string;
    errInternal: string;
    uploadTitle: string;
    fileInfo: string;
    btnUpload: string;
}

