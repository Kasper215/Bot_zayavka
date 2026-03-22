import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            outDir: 'public',
            buildBase: '/build/',
            scope: '/',
            registerType: 'autoUpdate',
            injectRegister: null,
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            manifest: false,
            devOptions: {
                enabled: true,
                type: 'module',
            }
        })
    ],
});

