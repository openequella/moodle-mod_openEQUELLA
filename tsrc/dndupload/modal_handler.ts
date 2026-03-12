import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {get_strings, StringRequest} from 'core/str';
import Templates from 'core/templates';
import {DndState, FORM_SELECTORS, ModalStrings, PLUGIN_NAME, UploadCancelledError, UploadData, DISPLAY_NONE_CLASS} from './types';
import {formatBytes} from './utils';

/**
 * Opens the upload modal, waits for the user to submit or cancel, and returns
 * the validated {@link UploadData} on success.
 * 
 * @returns A promise resolving to the complete upload metadata.
 * @throws {UploadCancelledError} If the user closes the modal without saving.
 */
export const launchUploadModal = async (state: DndState, file: File): Promise<UploadData> => {
    const strings = await loadModalStrings(file);
    const modal = await renderModal(strings, file);

    return new Promise<UploadData>((resolve, reject) =>
        attachModalEventHandlers(modal, {file: file, section: state.targetSection! }, strings, resolve, reject)
    );
};

/** Loads all localized strings required for the modal and validation. */
const loadModalStrings = async (file: File): Promise<ModalStrings> => {
    const keyPrefix = 'dnd.modal.';
    const {name, size} = file;

    const modalStringConfigs: Array<{ key: string; param?: any }> = [
        { key: 'err.copyright' },
        { key: 'err.title' },
        { key: 'err.desc' },
        { key: 'err.internal' },
        { key: 'title.upload' },
        { key: 'fileinfo', param: { name, size: formatBytes(size) } },
    ];

    const requests: StringRequest[] = [
        ...modalStringConfigs.map(({ key, param }) => ({ key: `${keyPrefix}${key}`, component: PLUGIN_NAME, param })),
        { key: 'upload', component: 'core' },
    ];

    const [
        errCopyright,
        errTitle,
        errDesc,
        errInternal,
        uploadTitle,
        fileInfo,
        btnUpload
    ] = await get_strings(requests);

    return { errCopyright, errTitle, errDesc, errInternal, uploadTitle, fileInfo, btnUpload };
};

/**
 * Renders the upload modal dialog using the `mod_equella/dnd_modal` Mustache template.
 * 
 * @param strings Localized strings for titles and labels.
 * @param file The file to be displayed in the modal info section.
 * @returns The created Modal instance.
 */
const renderModal = async (strings: ModalStrings, file: File) => {
    const formHtml = await Templates.render(`${PLUGIN_NAME}/dnd_modal`, {
        fileInfo: strings.fileInfo,
        fileName: file.name
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
 * Wires up the modal's save and hidden events to handle form validation and promise resolution.
 * 
 * @param modal The active modal instance.
 * @param partialData Incomplete upload data (file/section) to be merged with form values.
 * @param strings Localized strings for validation errors.
 * @param resolve Callback for successful data extraction.
 * @param reject Callback for user cancellation.
 */
const attachModalEventHandlers = (
    modal: any,
    partialData: Partial<UploadData>,
    strings: ModalStrings,
    resolve: (data: UploadData) => void,
    reject: (err: Error) => void,
) => {
    let finalData: UploadData | null = null;

    const onSave = (e: Event) => {
        e.preventDefault();
        const root = modal.getRoot()[0] as HTMLElement;
        try {
            const formData = extractFormData(root);
            assertFormDataValid(formData, strings);
            finalData = { ...formData, ...partialData } as UploadData;
            modal.destroy();
        } catch (err: any) {
            updateErrorDisplay(root, err.message);
        }
    };

    modal.getRoot().on(ModalEvents.save, onSave);
    modal.getRoot().on(ModalEvents.hidden, () => {
        if (finalData) {
            resolve(finalData);
        } else {
            reject(new UploadCancelledError());
        }
    });
};

/**
 * Reads the copyright, title, description, and keywords values from the modal form.
 * 
 * @param root The modal's root DOM element.
 * @returns An object containing the raw trimmed strings from the form fields.
 */
const extractFormData = (root: HTMLElement): Partial<UploadData> => {
    const { FORM_CONTAINER, COPYRIGHT, TITLE, DESC, KEYWORDS } = FORM_SELECTORS;
    const form = root.querySelector(FORM_CONTAINER) as HTMLFormElement;
    const formData = new FormData(form);
    const getField = (name: string) => (formData.get(name) as string ?? '').trim();

    return {
        copyright: getField(COPYRIGHT),
        title: getField(TITLE),
        desc: getField(DESC),
        keywords: getField(KEYWORDS),
    };
};

/**
 * Throws a descriptive error if any required form field is missing or too short.
 * 
 * @param data The partial data to validate.
 * @param strings Localized error messages.
 * @throws {Error} When validation fails for required fields.
 */
const assertFormDataValid = (data: Partial<UploadData>, strings: ModalStrings): void => {
    const { copyright, title, desc } = data;
    if (!copyright) throw new Error(strings.errCopyright);
    if (!title || title.length < 6) throw new Error(strings.errTitle);
    if (!desc || desc.length < 2) throw new Error(strings.errDesc);
};

/**
 * Displays or hides the validation error message inside the modal form.
 * 
 * @param root The modal's root DOM element.
 * @param message The error message to show, or null/empty to hide the error box.
 */
const updateErrorDisplay = (root: HTMLElement, message: string | null): void => {
    const errorBox = root.querySelector(FORM_SELECTORS.ERROR) as HTMLElement | null;
    if (!errorBox) {
        return;
    }

    const isVisible = !!message;
    errorBox.textContent = message ?? '';

    errorBox.classList.toggle(DISPLAY_NONE_CLASS, !isVisible);
};

