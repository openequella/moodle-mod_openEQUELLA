import {launchUploadModal} from '../dndupload/modal_handler';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {UploadCancelledError, FORM_SELECTORS, DISPLAY_NONE_CLASS} from '../dndupload/types';
import {createMockFile, createMockState, flushMicrotasks} from './test_helpers';

jest.mock('../dndupload/utils');

const {COPYRIGHT, ERROR, DESC, TITLE, KEYWORDS} = FORM_SELECTORS;

const setupTestContext = (formOverrides: Record<string, string> = {}) => {
    // Prepare DOM for custom modal
    const defaults = {
        [COPYRIGHT]: 'Yes',
        [TITLE]: 'My Valid Title',
        [DESC]: 'A valid description',
        [KEYWORDS]: 'test, jest',
    };
    const values = { ...defaults, ...formOverrides };

    const root = document.createElement('div');
    root.innerHTML = `
        <form id="equella-upload-form-container">
            ${Object.entries(values).map(([name, val]) => `<input name="${name}" value="${val}" />`).join('')}
        </form>
        <div id="${ERROR.substring(1)}" class="${DISPLAY_NONE_CLASS}"></div>
    `;

    // Prepare Mock Modal with Event Capture
    const events: Record<string, (e?: any) => void> = {};
    const mockModal = {
        setRemoveOnClose: jest.fn(),
        setButtonText: jest.fn(),
        show: jest.fn(),
        destroy: jest.fn(),
        getRoot: jest.fn().mockReturnValue({
            on: (evt: string, cb: any) => { events[evt] = cb; },
            0: root,
        }),
    };
    (ModalSaveCancel.create as jest.Mock).mockResolvedValue(mockModal);

    return {
        root,
        mockModal,
        save: () => {
            const e = { preventDefault: jest.fn() };
            events[ModalEvents.save]?.(e);
            return e;
        },
        cancel: () => events[ModalEvents.hidden]?.(),
        getError: () => root.querySelector(ERROR)?.textContent,
        isErrorVisible: () => !root.querySelector(ERROR)?.classList.contains(DISPLAY_NONE_CLASS),
    };
};

describe('Modal Handler - launchUploadModal', () => {
    const mockFile = createMockFile('test.pdf', 'content', 'application/pdf');
    const mockState = createMockState({ targetSection: '3' });

    beforeEach(() => jest.clearAllMocks());

    const launch = async (overrides = {}) => {
        const context = setupTestContext(overrides);
        const promise = launchUploadModal(mockState, mockFile);
        // Wait for modal initialization (strings loading, template rendering)
        await flushMicrotasks();
        return { ...context, promise };
    };

    it('resolves with complete UploadData when the form is valid and saved', async () => {
        const { promise, save, cancel, mockModal } = await launch();

        const e = save();
        expect(e.preventDefault).toHaveBeenCalled();
        expect(mockModal.destroy).toHaveBeenCalled();

        cancel(); // Simulates the modal hidden event triggering resolution

        await expect(promise).resolves.toEqual({
            file: mockFile,
            section: '3',
            copyright: 'Yes',
            title: 'My Valid Title',
            desc: 'A valid description',
            keywords: 'test, jest',
        });
    });

    it.each([
        { field: 'copyright', override: { [COPYRIGHT]: '' }, expected: 'Mock Copyright Error' },
        { field: 'title', override: { [TITLE]: 'Tiny' }, expected: 'Mock Title Error' },
        { field: 'description', override: { [DESC]: 'x' }, expected: 'Mock Desc Error' },
    ])('displays "$expected" when $field is invalid', async ({ override, expected }) => {
        const { promise, save, cancel, mockModal, isErrorVisible, getError } = await launch(override);

        save();

        expect(mockModal.destroy).not.toHaveBeenCalled();
        expect(isErrorVisible()).toBe(true);
        expect(getError()).toBe(expected);

        // Required to Ensure promise settles
        cancel(); 
        await expect(promise).rejects.toThrow(UploadCancelledError);
    });

    it('rejects with UploadCancelledError if the modal is hidden without saving', async () => {
        const { promise, cancel } = await launch();
        cancel();
        await expect(promise).rejects.toThrow(UploadCancelledError);
    });
});
