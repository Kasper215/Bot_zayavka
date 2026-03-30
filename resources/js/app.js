import './bootstrap';
import '../css/app.css';

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// Import only the specific icons you actually use here
// import { faUser } from '@fortawesome/free-solid-svg-icons';

// library.add(faUser);



import { createApp, h } from 'vue';
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// import router from './router'
import { useAlertStore } from './stores/utillites/useAlertStore'
import { i18n } from "./i18n";
import VueTheMask from "vue-the-mask";

// Debug error handler for mobile
window.onerror = function (msg, url, line) {
    console.error("App Error: " + msg + "\nAt: " + url + ":" + line);
    return false;
};

// PWA Registration with enhanced debugging
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        console.log('PWA: Attempting to register /sw.js...');
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
                console.log('PWA: ✅ Registered successfully. Scope:', reg.scope);
                // Notification status check
                if ('Notification' in window) {
                    console.log('PWA: Notification permission status:', Notification.permission);
                }

                reg.onupdatefound = () => {
                    const installingWorker = reg.installing;
                    console.log('PWA: 🔄 Update found, installing...');
                    installingWorker.onstatechange = () => {
                        if (installingWorker.state === 'installed') {
                            if (navigator.serviceWorker.controller) {
                                console.log('PWA: ✨ New content available! Please refresh.');
                            } else {
                                console.log('PWA: 📁 Content cached for offline use.');
                            }
                        }
                    };
                };
            })
            .catch(error => {
                console.error('PWA: ❌ Registration failed:', error);
            });
    });
} else {
    console.warn('PWA: Service Workers are not supported in this browser or over HTTP.');
}

window.addEventListener('beforeinstallprompt', (e) => {
    // This event fires when the browser thinks the app is installable
    console.log('PWA: 📥 beforeinstallprompt triggered! The app can be installed.');
    window.deferredPrompt = e;
});

window.addEventListener('appinstalled', () => {
    console.log('PWA: 🎉 Application was successfully installed!');
    window.deferredPrompt = null;
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {

        const app = createApp({ render: () => h(App, props) })

        app.use(createPinia())
        app.component('FontAwesomeIcon', FontAwesomeIcon)
        app.config.globalProperties.$notify = useAlertStore()

        return app
            .use(plugin)
            .use(ZiggyVue)
            .use(VueTheMask)
            .use(i18n)
            // .use(router)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
