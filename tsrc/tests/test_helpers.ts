import {DndState, UploadData} from '../dndupload/types';

/** Creates a mock {@link File} */
export const createMockFile = (name = 'test.txt', content = 'content', type = 'text/plain') =>
    new File([content], name, {type});

/** Creates a mock {@link DndState} with defaults, accepting partial overrides. */
export const createMockState = (overrides: Partial<DndState> = {}): DndState => ({
    courseId: 1,
    maxBytes: 1000,
    sessKey: 'test_key',
    targetSection: null,
    targetSectionId: null,
    ...overrides,
});

/** Creates a mock {@link UploadData} with defaults, accepting partial overrides. */
export const createUploadData = (overrides: Partial<UploadData> = {}): UploadData => ({
    file: createMockFile(),
    section: '1',
    title: 'Test Title',
    copyright: 'CC-BY',
    desc: 'Description',
    keywords: 'tag1',
    ...overrides,
});

/** Flushes all pending microtasks (resolved promises, async/await continuations). */
export const flushMicrotasks = () => new Promise(resolve => setTimeout(resolve, 0));

/** Creates a mock {@link XMLHttpRequest} that fires `onload` or `onerror` synchronously on `send()`. */
export const createMockXhr = (shouldFail = false) => {
    const mockXhr: any = {
        open: jest.fn(),
        send: jest.fn(function (this: any) {
            if (shouldFail && this.onerror) this.onerror();
            else if (!shouldFail && this.onload) this.onload();
        }),
        upload: {onprogress: null},
        setRequestHeader: jest.fn(),
        status: 200,
        responseText: JSON.stringify({error: 0}),
    };
    global.XMLHttpRequest = jest.fn(() => mockXhr) as any;
    return mockXhr;
};