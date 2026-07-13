const mockTranslations: Record<string, string> = {
    'dnd.modal.err.copyright': 'Mock Copyright Error',
    'dnd.modal.err.title': 'Mock Title Error',
    'dnd.modal.err.desc': 'Mock Desc Error',
    'dnd.modal.err.internal': 'Mock Internal Error',
    'dnd.modal.title.upload': 'Upload Title',
    'dnd.modal.fileinfo': 'File Info',
    'upload': 'Upload',
    'dnd.err.title.uploadfailed': 'Upload Failed',
    'dnd.err.network': 'Network Error',
    'dnd.err.folder': 'Folder Error',
};

export const get_string = jest.fn((key: string) =>
    Promise.resolve(mockTranslations[key] || `Missing Translation: ${key}`)
);

export const get_strings = jest.fn((requests: any[]) =>
    Promise.resolve(requests.map(r => mockTranslations[r.key] || `Missing Translation: ${r.key}`))
);