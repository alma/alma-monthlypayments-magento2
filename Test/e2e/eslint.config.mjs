import globals from "globals";
import pluginJs from "@eslint/js";
import tseslint from "typescript-eslint";


/** @type {import('eslint').Linter.Config[]} */
export default [
  {
    files: ["**/*.{js,mjs,cjs,ts}"]
  },
  {
    ignores: [
      "playwright-report/",
      ".bdd/",
    ]
  },
  {
    languageOptions: { globals: globals.browser }
  },
  {
    rules: {
      "eol-last": "error",
    },
  },
  pluginJs.configs.recommended,
  ...tseslint.configs.recommended,
];
