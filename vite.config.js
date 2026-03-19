import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

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
        // VitePWA({
        //     registerType: 'autoUpdate',
        //     injectRegister: 'auto',
        //     manifest: {
        //         name: 'BioBook',
        //         short_name: 'BioBook',
        //         description: 'Система загрузки видео',
        //         theme_color: '#4f46e5',
        //         icons: [
        //             {
        //                 src: '/пнглого.png',
        //                 sizes: '192x192',
        //                 type: 'image/png'
        //             },
        //             {
        //                 src: '/пнглого.png',
        //                 sizes: '512x512',
        //                 type: 'image/png'
        //             }
        //         ]
        //     }
        // })
    ],
});
