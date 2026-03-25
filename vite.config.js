import { defineConfig } from 'vite';
import { globSync } from 'glob';
import path from 'node:path';

const styleEntries = globSync('resources/assets/**/css/*.css');

export default defineConfig({
    css: {
        postcss: './postcss.config.js',
    },
    build: {
        outDir: 'assets',
        emptyOutDir: false,
        rollupOptions: {
            input: styleEntries,
            output: {
                assetFileNames(assetInfo) {
                    const originalName = assetInfo.originalFileNames?.[0] ?? '';
                    if (originalName.endsWith('.css')) {
                        return originalName.replace(/^resources\//, '');
                    }

                    return 'core/js/[name][extname]';
                },
                entryFileNames: 'core/js/[name].js',
                chunkFileNames: 'core/js/[name].js',
            },
        },
    },
    resolve: {
        alias: {
            '@assets': path.resolve(__dirname, 'resources/assets'),
        },
    },
});
