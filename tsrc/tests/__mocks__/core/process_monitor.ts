export const processMonitor = {
    addLoadingProcess: jest.fn(() => ({
        setPercentage: jest.fn(),
        finish: jest.fn(),
        remove: jest.fn(),
        setError: jest.fn(),
    })),
};