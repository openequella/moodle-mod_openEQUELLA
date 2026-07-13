import { formatBytes, getModEquellaString, showErrorToast } from '../dndupload/utils';
import { get_string } from 'core/str';
import { add as addToast } from 'core/toast';

describe('utils', () => {
    describe('formatBytes', () => {
        it('formats bytes correctly', () => {
            expect(formatBytes(0)).toBe('0B');
            expect(formatBytes(1024)).toBe('1KB');
            expect(formatBytes(1500)).toBe('1.5KB');
            expect(formatBytes(1048576)).toBe('1MB');
        });
    });

    describe('getModEquellaString', () => {
        it('calls get_string with correct component', async () => {
            // The function signature takes (key, param), so we pass 'value' directly
            await getModEquellaString('testKey', 'value'); 
            expect(get_string).toHaveBeenCalledWith('testKey', 'mod_equella', 'value');
        });
    });

    describe('showErrorToast', () => {
        it('calls addToast with error configuration', async () => {
            await showErrorToast('Something went wrong');
            
            expect(addToast).toHaveBeenCalledWith('Something went wrong', {
                type: 'danger',
                title: 'Upload Failed'
            });
        });
    });
});