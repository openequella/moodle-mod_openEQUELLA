/**
 * Since Moodle Core does not provide its own TypeScript definitions, we must declare by ourselves
 * to make TS happy with the imports. However, due to the unknowns of the Mooodle Core modules, lots
 * of "any" types are used here.
 */

declare module 'core/*' {
    const value: any;
    export default value;
}

declare module 'core/str' {
    export const get_string: (key: string, component: string, param?: any) => Promise<string>;
}

declare module 'core/toast' {
    export const add: (message: string, configuration?: any) => Promise<void>;
}

declare module 'core/process_monitor' {
    /** The process definition data used to initialize a new process. */
    export interface ProcessDefinition {
        name: string;
        percentage?: number;
        error?: string;
        url?: string;
    }

    export interface LoadingProcess {
        setPercentage(percentage: number): void;
        setError(error: string): void;
        finish(): void;
        remove(): void;
    }
    
    export const processMonitor: {
        addLoadingProcess(definition: ProcessDefinition): LoadingProcess;
    };
}

declare module 'core_courseformat/courseeditor' {
    export const getCourseEditor: (courseId: number)=> any;
}

declare const M: any;
declare const $: any;