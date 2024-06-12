module.exports = {
    extends: ["json:recommended"],
    ignorePatterns: ["!/tools/linters/.eslintrc.yml", "!/tools/linters/.stylelintrc.json"],
    parserOptions: {
        ecmaVersion: 2015,
        sourceType: "module"
    }
};
