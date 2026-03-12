import {UploadCancelledError} from '../dndupload/types';
import {processMonitor} from 'core/process_monitor';
import {launchUploadModal} from '../dndupload/modal_handler';
import {handleFileUpload} from '../dndupload/uploader';
import {getCourseEditor} from 'core_courseformat/courseeditor';
import {createMockFile, createMockState, createUploadData, createMockXhr} from './test_helpers';

jest.mock('../dndupload/modal_handler');

// Instantiate reusable mock objects using the factories defined in __mocks__
const mockProcess = processMonitor.addLoadingProcess({} as any);
const mockEditor = getCourseEditor(0);

describe('Uploader - handleFileUpload', () => {
    const mockFile = createMockFile();
    const mockState = createMockState({courseId: 1, targetSectionId: 101, targetSection: '1'});

    beforeEach(() => {
        jest.clearAllMocks();

        // Configure dependencies to return our persistent mock instances
        (processMonitor.addLoadingProcess as jest.Mock).mockReturnValue(mockProcess);
        (getCourseEditor as jest.Mock).mockReturnValue(mockEditor);

        createMockXhr(false);

        // Required for setTimeout being used in `handleFileUpload` finally block.
        jest.spyOn(global, 'setTimeout').mockImplementation((cb: any) => cb() as any);
    });

    afterAll(() => jest.restoreAllMocks());

    describe('User Cancellation', () => {
        it('exits silently without starting upload process', async () => {
            (launchUploadModal as jest.Mock).mockRejectedValueOnce(new UploadCancelledError());

            await expect(handleFileUpload(mockFile, mockState)).resolves.toBeUndefined();
            expect(processMonitor.addLoadingProcess).not.toHaveBeenCalled();
            expect(global.XMLHttpRequest).not.toHaveBeenCalled();
        });
    });

    describe('Modal Errors', () => {
        it('propagates unexpected errors from the modal', async () => {
            const error = new Error('Unexpected modal crash');
            (launchUploadModal as jest.Mock).mockRejectedValueOnce(error);

            await expect(handleFileUpload(mockFile, mockState)).rejects.toThrow(error);
            expect(processMonitor.addLoadingProcess).not.toHaveBeenCalled();
        });
    });

    describe('Upload Flow', () => {
        const mockModalSuccess = () => (launchUploadModal as jest.Mock).mockResolvedValueOnce(createUploadData({file: mockFile}));

        it('performs XHR upload and refreshes section on success', async () => {
            mockModalSuccess();
            const xhr = createMockXhr(false);

            await handleFileUpload(mockFile, mockState);

            expect(xhr.open).toHaveBeenCalledWith('POST', expect.stringContaining('dndupload.php'), true);
            expect(mockEditor.dispatch).toHaveBeenCalledWith('sectionState', [101]);
            expect(mockProcess.finish).toHaveBeenCalled();
            expect(mockProcess.remove).toHaveBeenCalled();
        });

        it('handles network errors by setting process error state', async () => {
            mockModalSuccess();
            createMockXhr(true);
            const networkError = 'Network Error';
            
            await expect(handleFileUpload(mockFile, mockState)).rejects.toThrow(networkError);

            expect(mockProcess.setError).toHaveBeenCalledWith(networkError);
            expect(mockEditor.dispatch).not.toHaveBeenCalled();
            expect(mockProcess.remove).toHaveBeenCalled();
        });
    });
});