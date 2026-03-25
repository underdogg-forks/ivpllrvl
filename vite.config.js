import { defineConfig } from 'vite';
import { globSync } from 'glob';
import path from 'node:path';

const styleEntries = globSync('resources/assets/**/{sass,scss}/*.scss', {
    ignore: ['**/_*.scss'],
});

const scriptEntries = globSync('resources/assets/{js,core/js}/**/*.js', {
    ignore: ['**/.gitignore'],
});

const toInputEntries = (files) =>
    files.reduce((acc, file) => {
        const relative = file.replace(/^resources\/assets\//, '');

        if (file.endsWith('.scss')) {
            const cssKey = relative
                .replace('/sass/', '/css/')
                .replace('/scss/', '/css/')
                .replace(/\.scss$/, '');
            acc[cssKey] = file;

            return acc;
        }

        acc[relative.replace(/\.js$/, '')] = file;

        return acc;
    }, {});

export default defineConfig({
    css: {
        postcss: './postcss.config.js',
    },
    build: {
        outDir: 'public/assets',
        emptyOutDir: false,
        rollupOptions: {
            input: toInputEntries([...styleEntries, ...scriptEntries]),
            output: {
                assetFileNames: '[name][extname]',
                entryFileNames: '[name].js',
                chunkFileNames: 'js/chunks/[name].js',
            },
        },
    },
    resolve: {
        alias: {
            '@assets': path.resolve(__dirname, 'resources/assets'),
        },
    },
});
