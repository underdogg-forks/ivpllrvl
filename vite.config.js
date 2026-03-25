import { defineConfig } from 'vite';
import glob from 'glob';
import path from 'node:path';

const styleEntries = glob.sync('resources/assets/**/{sass,scss}/*.scss', {
    ignore: ['**/_*.scss'],
});

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
                    if (originalName.endsWith('.scss')) {
                        const cssPath = originalName
                            .replace(/^resources\//, '')
                            .replace('/sass/', '/css/')
                            .replace('/scss/', '/css/')
                            .replace(/\.scss$/, '.css');

                        return cssPath;
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
