module.exports = {
    ignorePatterns: ["!/tools/linters/.eslintrc.yml", "!/tools/linters/.stylelintrc.json"],
    parserOptions: {
        ecmaVersion: 2015,
        sourceType: "module"
    },
    overrides: [
        {
            files: ["*.json"],
            extends: ["plugin:jsonc/recommended-with-json"],
            parser: "jsonc-eslint-parser",
        }
    ]
};
