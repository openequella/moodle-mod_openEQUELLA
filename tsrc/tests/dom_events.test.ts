import {captureDropTransfers, registerUploadHandler} from '../dndupload/dom_events';
import {getCourseEditor} from 'core_courseformat/courseeditor';
import {handleFileUpload} from '../dndupload/uploader';
import {createMockState, createMockFile} from './test_helpers';

jest.mock('../dndupload/utils');
jest.mock('../dndupload/uploader');

global.console.warn = jest.fn();

/**
 * Simulates a 'drop' event on the document with the specified data transfer types.
 * 
 * @param types A list of data types to simulate (e.g. ['Files']). Defaults to ['Files'].
 * @returns The mock DataTransfer object attached to the event.
 */
const dispatchDropEvent = (types = ['Files']) => {
    const dt = { types, items: [] };
    const event = new Event('drop', { bubbles: true });
    Object.defineProperty(event, 'dataTransfer', { value: dt });
    document.dispatchEvent(event);
    return dt;
};

describe('DOM Events', () => {
    beforeEach(() => jest.clearAllMocks());

    describe('captureDropTransfers', () => {
        it('stores DataTransfer reference on file drops', () => {
            const ref = captureDropTransfers();
            expect(ref.value).toBeNull();
            
            const dt = dispatchDropEvent(['Files']);
            expect(ref.value).toBe(dt);
        });

        it('ignores non-file drops', () => {
            const ref = captureDropTransfers();
            dispatchDropEvent(['text/plain']);
            expect(ref.value).toBeNull();
        });
    });

    describe('registerUploadHandler', () => {
        it('aborts gracefully if Course Editor is missing', () => {
            (getCourseEditor as jest.Mock).mockReturnValue(null);
            
            registerUploadHandler(createMockState(), {value: null});
            expect(console.warn).toHaveBeenCalledWith(expect.stringContaining('Course editor not found'));
        });

        it('intercepts uploadFiles to route through openEQUELLA uploader', async () => {
            const editor = { uploadFiles: null as any };
            (getCourseEditor as jest.Mock).mockReturnValue(editor);
            
            const state = createMockState();
            registerUploadHandler(state, {value: null});

            // Verify interception
            expect(editor.uploadFiles).toBeInstanceOf(Function);

            // Trigger the intercepted function
            const file = createMockFile();
            await editor.uploadFiles(101, 2, [file]);

            // Verify state updates and delegation
            expect(state.targetSectionId).toBe(101);
            expect(state.targetSection).toBe('2');
            expect(handleFileUpload).toHaveBeenCalledWith(file, state);
        });
    });
});