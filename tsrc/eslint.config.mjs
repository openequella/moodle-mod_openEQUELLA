import js from "@eslint/js";
import tseslint from "typescript-eslint";
import globals from "globals";

export default [
    js.configs.recommended, // Basic JS rules
    ...tseslint.configs.recommended, // Basic TS rules
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                M: "readonly", // Moodle global
                $: "readonly", // jQuery global
            },
        },
    },
    {
        files: ["**/*.d.ts", "tests/__mocks__/**/*.ts", "tests/**/*.test.ts"],
        rules: {
            "@typescript-eslint/no-explicit-any": "off",
        },
    },
    {
        files: ["**/*.ts"],
        rules: {
            "@typescript-eslint/no-explicit-any": "off", // Moodle has lots of "any".
            "no-console": "off",
        },
    },
    {
        ignores: ["node_modules", "dist", "build", "webpack.config.js"],
    },
];