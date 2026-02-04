/**
 * Since Moodle Core does not provide its own TypeScript definitions, we must declare by ourselves
 * to make TS happy with the imports. However, due to the unknowns of the Mooodle Core modules, lots
 * of "any" types are used here.
 */

declare module 'core/*' {
    const value: any;
    export default value;
}

declare module 'core_form/*' {
    const value: any;
    export default value;
}

declare module 'core/str' {
    export const get_string: (key: string, component: string, param?: any) => Promise<string>;
}

declare const M: any;
declare const $: any;