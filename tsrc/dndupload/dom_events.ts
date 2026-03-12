import {DndState, DropItem} from './types';
import {formatBytes, getModEquellaString, showErrorToast} from './utils';
import {getCourseEditor} from "core_courseformat/courseeditor";
import {handleFileUpload} from "./uploader";

/**
 * Intercepts the native Moodle course editor's file upload process.
 * Replaces the default upload behavior with a custom workflow that validates
 * files, checks for size limits, and displays the openEQUELLA metadata modal.
 */
export const registerUploadHandler = (
    state: DndState,
    dropTransferRef: { value: DataTransfer | null },
): void => {
    const courseEditor = getCourseEditor(state.courseId);

    if (!courseEditor) {
        console.warn('openEQUELLA: Course editor not found. DND custom interception failed.');
        return;
    }

    courseEditor.uploadFiles = async (sectionId: number, sectionNum: number, files: File[]) => {
        state.targetSection = sectionNum.toString();
        state.targetSectionId = sectionId;

        const validFiles = await filterValidFiles(files, dropTransferRef);
        dropTransferRef.value = null;
        if (validFiles.length == 0) return;

        if (await rejectOversizedFiles(state, validFiles)) return;

        for (const file of validFiles) {
            await handleFileUpload(file, state);
        }
    };
};

/**
 * Attaches a `drop` listener that stores the latest {@link DataTransfer} so directory detection can be performed later.
 * @returns A ref object whose `.value` holds the most recent drop's {@link DataTransfer}.
 */
export const captureDropTransfers = (): { value: DataTransfer | null } => {
    const ref: { value: DataTransfer | null } = { value: null };
    document.addEventListener('drop', (e: DragEvent) => {
        if (isFilesDrag(e)) {
            ref.value = e.dataTransfer;
        }
    }, true);
    return ref;
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
const extractDropItems = (dt: DataTransfer): Array<DropItem> =>
    Array.from(dt.items)
        .map(item => ({
            entry: item.webkitGetAsEntry(),
            file: item.getAsFile(),
        }))
        .filter((item): item is { entry: FileSystemEntry; file: File } => !!item.entry && !!item.file)
        .map(({ entry, file }) => ({ file, isFile: entry.isFile }));

/**
 * Filters out directories from the dropped items.
 * Falls back to the raw `files` array when {@link DataTransfer} is unavailable.
 */
const filterValidFiles = async (
    files: File[],
    dropTransferRef: { value: DataTransfer | null },
): Promise<File[]> => {
    if (!dropTransferRef.value) return files;

    const dropItems = extractDropItems(dropTransferRef.value);

    const {validFiles, folderNames} = dropItems.reduce<{validFiles: File[], folderNames: string[]}>((acc, item)=> {
        item.isFile ? acc.validFiles.push(item.file) : acc.folderNames.push(item.file.name)
        return acc;
    }, { validFiles: [], folderNames: []})

    if (folderNames.length > 0) {
        const msg = await getModEquellaString('dnd.err.folder', folderNames.join(', '));
        await showErrorToast(msg);
        return [];
    }

    return validFiles;
};

/**
 * Checks if any files exceed the maximum allowed size. If so, displays an error
 * toast and returns true to indicate the upload should be aborted.
 */
const rejectOversizedFiles = async (state: DndState, files: File[]): Promise<boolean> => {
    if (state.maxBytes <= 0) return false;

    const oversizedNames = files
        .filter(file => file.size > state.maxBytes)
        .map(file => file.name);

    if (oversizedNames.length === 0) return false;

    const msg = await getModEquellaString('dnd.err.maxbytes', {
        file: oversizedNames.join(", "),
        size: formatBytes(state.maxBytes),
    });
    await showErrorToast(msg);
    return true;
};

