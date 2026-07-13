import {get_string} from 'core/str';
import {add as addToast} from 'core/toast';
import {PLUGIN_NAME} from './types';

/**
 * Formats a byte count into a human-readable string (e.g. "1.5MB").
 * @param bytes The number of bytes.
 */
export const formatBytes = (bytes: number): string =>
    new Intl.NumberFormat(undefined, {
        style: 'unit',
        unit: 'byte',
        notation: 'compact',
        unitDisplay: 'narrow',
    }).format(bytes);

/**
 * Helper to fetch a localized string for the mod_equella component.
 * @param key The string key.
 * @param param Optional parameters for the string.
 */
export const getModEquellaString = (key: string, param?: string | object): Promise<string> =>
    get_string(key, PLUGIN_NAME, param);

/** Displays a danger toast notification with a fixed title. */
export const showErrorToast = async (msg: string): Promise<void> => {
    await addToast(msg, {
        type: 'danger',
        title: await getModEquellaString('dnd.err.title.uploadfailed')
    });
};
