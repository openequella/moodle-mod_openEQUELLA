import Config from 'core/config';
import {DndState, InitConfig} from './types';
import {captureDropTransfers, registerUploadHandler} from './dom_events';

/**
 * Called from PHP to initialise DND upload. See `lib.php`.
 * @param config Initial configuration passed from PHP.
 */
export const init = (config: InitConfig): void => {
    const state: DndState = {
        ...config,
        sessKey: Config.sesskey,
        targetSection: null,
        targetSectionId: null,
    };

    const dropTransferRef = captureDropTransfers();
    registerUploadHandler(state, dropTransferRef);
};
