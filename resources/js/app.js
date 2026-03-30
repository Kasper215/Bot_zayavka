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

// PWA Registration with enhanced debugging and Persistent Overlay
let deferredPrompt;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
                console.log('PWA: ✅ Registered');
                // Auto-request for notifications on first interaction
                const requestPushPrompt = () => {
                   if ('Notification' in window && Notification.permission === 'default') {
                       Notification.requestPermission().then(permission => {
                           console.log('PWA: Notification permission', permission);
                       });
                   }
                   window.removeEventListener('click', requestPushPrompt);
                };
                window.addEventListener('click', requestPushPrompt);
            });
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    deferredPrompt = e;
    console.log('PWA: 📥 Ready to install');
    showInstallOverlay();
});

function showInstallOverlay() {
    if (document.getElementById('pwa-install-overlay')) return;
    
    const overlay = document.createElement('div');
    overlay.id = 'pwa-install-overlay';
    overlay.innerHTML = `
        <div class="pwa-content">
            <div class="pwa-icon">✨</div>
            <div class="pwa-text">
                <strong>BioBook PRO</strong>
                <span>Установите для мгновенных пуш-уведомлений</span>
            </div>
            <button id="pwa-install-btn">УСТАНОВИТЬ</button>
            <button id="pwa-close-btn">✕</button>
        </div>
    `;
    document.body.appendChild(overlay);
    
    document.getElementById('pwa-install-btn').onclick = () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('PWA: User accepted');
                overlay.remove();
            }
            deferredPrompt = null;
        });
    };
    
    document.getElementById('pwa-close-btn').onclick = () => overlay.remove();
}

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
