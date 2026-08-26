import prettier from 'eslint-plugin-prettier'
import eslintPluginPrettierRecommended from 'eslint-plugin-prettier/recommended'
import eslintPluginReact from 'eslint-plugin-react'
import eslintPluginReactHooks from 'eslint-plugin-react-hooks'
import tseslint from '@typescript-eslint/eslint-plugin'
import tsParser from '@typescript-eslint/parser'
import globals from 'globals'

export default [
    { ignores: ['eslint.config.js'] },
    {
        files: ['src/**/*.{ts,tsx}'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                project: './tsconfig.json',
                ecmaFeatures: {
                    jsx: true,
                },
            },
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        plugins: {
            '@typescript-eslint': tseslint,
            react: eslintPluginReact,
            'react-hooks': eslintPluginReactHooks,
            prettier,
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
        rules: {
            ...tseslint.configs.recommended.rules,
            ...eslintPluginReactHooks.configs.recommended.rules,
            'prettier/prettier': 'error',
        },
    },

    eslintPluginPrettierRecommended,
]
