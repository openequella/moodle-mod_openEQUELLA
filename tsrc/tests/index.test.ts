import { init } from '../dndupload';
import { captureDropTransfers, registerUploadHandler } from '../dndupload/dom_events';

jest.mock('../dndupload/dom_events');

describe('index (dndupload)', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        (captureDropTransfers as jest.Mock).mockReturnValue('mockRef');
    });

    it('initialises dnd state and registers handler', () => {
        const config = { courseId: 100, maxBytes: 500 };

        init(config);

        expect(captureDropTransfers).toHaveBeenCalled();
        expect(registerUploadHandler).toHaveBeenCalledWith(
            expect.objectContaining({
                ...config,
                sessKey: 'test_sesskey',
                targetSection: null
            }),
            'mockRef'
        );
    });
});