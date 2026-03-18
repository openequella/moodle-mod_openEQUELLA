import {get_string} from 'core/str';
import {getModEquellaString} from './utils';
import {DndState, UPLOAD_ENDPOINT, UploadCancelledError, UploadData} from './types';
import {LoadingProcess, processMonitor} from 'core/process_monitor';
import {getCourseEditor} from 'core_courseformat/courseeditor';
import {launchUploadModal} from "./modal_handler";

const PROCESS_REMOVE_DELAY_MS = 2000;

/**
 * Attempts to acquire upload data, returning null if the user cancels.
 */
const tryAcquireUploadData = async (state: DndState, file: File): Promise<UploadData | null> => {
    try {
        return await launchUploadModal(state, file);
    } catch (e) {
        if (e instanceof UploadCancelledError) return null;
        throw e;
    }
};

/**
 * Handles a single file upload: opens the modal, performs the XHR upload, and refreshes the course section state.
 */
export const handleFileUpload = async (file: File, state: DndState): Promise<void> => {
    const data = await tryAcquireUploadData(state, file);
    if (!data) return;

    const process = processMonitor.addLoadingProcess({ name: data.file.name });

    try {
        await performUpload(data, state, process);
        await refreshSectionState(state);
    } catch (e) {
        process.setError(e instanceof Error ? e.message : String(e));
        throw e;
    } finally {
        setTimeout(() => process.remove(), PROCESS_REMOVE_DELAY_MS);
    }
};

/**
 * Dispatches a Moodle course-editor state refresh so the new activity
 * appears without a full page reload.
 */
const refreshSectionState = async (state: DndState): Promise<void> => {
    const {courseId, targetSectionId} = state;
    const editor = getCourseEditor(courseId);
    if (!editor) return;

    const [action, payload] = targetSectionId ? ['sectionState', [targetSectionId]] : ['courseState', []];
    await editor.dispatch(action, payload);
};

/**
 * Sends the upload XHR to the openEQUELLA module's endpoint (`dndupload.php`).
 * Returns a promise that resolves when the server confirms the upload is successful.
 */
const performUpload = (data: UploadData, state: DndState, process: LoadingProcess): Promise<void> =>
    new Promise<void>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', UPLOAD_ENDPOINT, true);
        attachXhrHandlers(xhr, process, resolve, reject);
        xhr.send(buildFormData(data, state));
    });

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

