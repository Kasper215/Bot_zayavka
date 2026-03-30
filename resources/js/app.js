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
    
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    
    let subtext = 'Установите для мгновенных пуш-уведомлений';
    if (isIOS && !isStandalone) {
        subtext = 'Нажмите <strong>"Поделиться"</strong> и <strong>"На экран Домой"</strong> 📲';
    }
    
    const overlay = document.createElement('div');
    overlay.id = 'pwa-install-overlay';
    overlay.innerHTML = `
        <div class="pwa-content">
            <div class="pwa-icon">✨</div>
            <div class="pwa-text">
                <strong>BioBook</strong>
                <span>${subtext}</span>
            </div>
            ${(isIOS && !isStandalone) ? '' : '<button id="pwa-install-btn">УСТАНОВИТЬ</button>'}
            <button id="pwa-close-btn">✕</button>
        </div>
    `;
    document.body.appendChild(overlay);
    
    if (document.getElementById('pwa-install-btn')) {
        document.getElementById('pwa-install-btn').onclick = async () => {
            // ВАЖНО: prompt() должен идти ПЕРВЫМ — до любых async операций
            // иначе Chrome теряет контекст пользовательского жеста и игнорирует запрос
            if (!deferredPrompt) {
                console.log('PWA: No install prompt — requesting push only');
                if (window.pwa) window.pwa.registerPush();
                return;
            }

            // 1) Показываем системный диалог установки синхронно
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;

            if (outcome === 'accepted') {
                console.log('PWA: ✅ App installed');
                overlay.remove();
                // 2) После установки запрашиваем push-разрешение
                if (window.pwa) window.pwa.registerPush();
            }
        };
    }
    
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

        // Define a global subscription helper
        window.pwa = {
            registerPush: async (vapidKey) => {
                try {
                    const key = vapidKey || props?.initialPage?.props?.vapid_public_key;
                    if (!key || !('serviceWorker' in navigator)) return;
                    
                    const registration = await navigator.serviceWorker.ready;
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') return;

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: window.pwa.urlBase64ToUint8Array(key)
                    });

                    await fetch('/notifications/subscribe', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': props.initialPage.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content
                        },
                        body: JSON.stringify(subscription)
                    });
                    console.log('PWA: ✅ Subscribed');
                } catch (e) {
                    console.error('PWA: ❌ Subscribe failed', e);
                }
            },
            urlBase64ToUint8Array: (base64String) => {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }
        };

        // Auto-subscribe on first click
        const autoSub = () => {
             window.pwa.registerPush();
             window.removeEventListener('click', autoSub);
        };
        window.addEventListener('click', autoSub);

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
