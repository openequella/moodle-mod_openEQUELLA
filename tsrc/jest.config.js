module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'jsdom',
  moduleNameMapper: {
    '^core/(.*)$': '<rootDir>/tests/__mocks__/core/$1',
    '^core_courseformat/(.*)$': '<rootDir>/tests/__mocks__/core_courseformat/$1',
  },
};