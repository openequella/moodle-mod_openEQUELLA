export default {
    create: jest.fn(() => Promise.resolve({
        setRemoveOnClose: jest.fn(),
        setButtonText: jest.fn(),
        show: jest.fn(),
        destroy: jest.fn(),
        getRoot: jest.fn(() => ({
            on: jest.fn(),
            0: document.createElement('div'),
        })),
    })),
};