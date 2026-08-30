import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        cors: true,
        headers: {
            'Cross-Origin-Opener-Policy': 'same-origin',
            'Cross-Origin-Embedder-Policy': 'require-corp',
        },
    },
    build: {
        outDir: 'public/build',
        chunkSizeWarningLimit: 800,
        sourcemap: false,
        rollupOptions: {
            onwarn(warning, warn) {
                if (warning.plugin === 'vite:esbuild') {
                    throw new Error(`Vite ESBuild error: ${warning.message}`)
                }
                warn(warning)
            },
            output: {
                entryFileNames: 'assets/[hash].js',
                chunkFileNames: 'assets/[hash].js',
                assetFileNames: 'assets/[hash].[ext]',
                manualChunks(id) {
                    // this thing fixes issues with vite build minif of sentry
                    if (id.includes('node_modules') && !id.includes('sentry')) {
                        return id.toString().split('node_modules/')[1].split('/')[0]
                    }
                },
            },
        },
    },
})
