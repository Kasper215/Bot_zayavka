import './bootstrap';
import '../css/app.css';

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { createApp, h } from 'vue';
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { useAlertStore } from './stores/utillites/useAlertStore'
import { i18n } from "./i18n";
import VueTheMask from "vue-the-mask";

// Debug error handler for mobile
window.onerror = function (msg, url, line) {
    console.error("App Error: " + msg + "\nAt: " + url + ":" + line);
    return false;
};

// ─── PWA Infrastructure ───────────────────────────────────────────────────────

let deferredPrompt = null;
let swRegistration = null;

// 1) Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
                swRegistration = reg;
                console.log('PWA: ✅ SW Registered, scope:', reg.scope);
            })
            .catch(err => {
                console.error('PWA: ❌ SW Registration failed:', err);
            });
    });
}

// 2) Capture install prompt — do NOT call e.preventDefault() so Chrome can
//    still show its native install button in the address bar as fallback.
//    We store the event to call it later from our button.
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault(); // prevent auto-showing native mini-infobar
    deferredPrompt = e;
    console.log('PWA: 📥 beforeinstallprompt fired — site is installable!');
    showInstallOverlay();
});

// 3) If beforeinstallprompt never fires within 4s, show push-only overlay
setTimeout(() => {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (!deferredPrompt && !isStandalone && !document.getElementById('pwa-install-overlay')) {
        console.warn('PWA: ⚠️ beforeinstallprompt did NOT fire. Showing push-only overlay.');
        showPushOnlyOverlay();
    }
}, 4000);

// ─── Install Overlay (when beforeinstallprompt DID fire) ──────────────────────

function showInstallOverlay() {
    if (document.getElementById('pwa-install-overlay')) return;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

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

    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.onclick = async () => {
            if (!deferredPrompt) {
                console.warn('PWA: deferredPrompt gone');
                return;
            }
            // Call prompt() SYNCHRONOUSLY in the user gesture handler
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            console.log('PWA: Install outcome:', outcome);

            if (outcome === 'accepted') {
                overlay.remove();
                // Request push AFTER install
                setTimeout(() => window.pwa?.registerPush(), 1000);
            }
        };
    }

    document.getElementById('pwa-close-btn').onclick = () => overlay.remove();
}

// ─── Push-only Overlay (when beforeinstallprompt did NOT fire) ────────────────

function showPushOnlyOverlay() {
    if (document.getElementById('pwa-install-overlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'pwa-install-overlay';
    overlay.innerHTML = `
        <div class="pwa-content">
            <div class="pwa-icon">🔔</div>
            <div class="pwa-text">
                <strong>BioBook</strong>
                <span>Включите уведомления, чтобы не пропускать новости</span>
            </div>
            <button id="pwa-push-btn">ВКЛЮЧИТЬ</button>
            <button id="pwa-close-btn">✕</button>
        </div>
    `;
    document.body.appendChild(overlay);

    document.getElementById('pwa-push-btn').onclick = () => {
        window.pwa?.registerPush();
        overlay.remove();
    };
    document.getElementById('pwa-close-btn').onclick = () => overlay.remove();
}

// ─── App Installed Event ──────────────────────────────────────────────────────

window.addEventListener('appinstalled', () => {
    console.log('PWA: 🎉 App installed as WebAPK!');
    deferredPrompt = null;
});

// ─── Inertia App ─────────────────────────────────────────────────────────────

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {

        const app = createApp({ render: () => h(App, props) })

        app.use(createPinia())
        app.component('FontAwesomeIcon', FontAwesomeIcon)
        app.config.globalProperties.$notify = useAlertStore()

        // Global push subscription helper
        window.pwa = {
            registerPush: async (vapidKey) => {
                try {
                    const key = vapidKey || props?.initialPage?.props?.vapid_public_key;
                    if (!key) {
                        console.error('PWA: ❌ No VAPID key available');
                        return;
                    }
                    if (!('serviceWorker' in navigator)) {
                        console.error('PWA: ❌ Service Worker not supported');
                        return;
                    }

                    const registration = await navigator.serviceWorker.ready;
                    console.log('PWA: SW ready for push subscription');

                    const permission = await Notification.requestPermission();
                    console.log('PWA: Notification permission:', permission);
                    if (permission !== 'granted') return;

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: window.pwa.urlBase64ToUint8Array(key)
                    });

                    const csrfToken = props?.initialPage?.props?.csrf_token
                        || document.querySelector('meta[name="csrf-token"]')?.content;

                    const resp = await fetch('/notifications/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(subscription)
                    });

                    if (resp.ok) {
                        console.log('PWA: ✅ Push subscription saved to server');
                    } else {
                        console.error('PWA: ❌ Server rejected subscription:', resp.status);
                    }
                } catch (e) {
                    console.error('PWA: ❌ Subscribe failed:', e.message);
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

        // Auto-request push on first click (works even without WebAPK)
        const autoSub = () => {
            window.pwa.registerPush();
            window.removeEventListener('click', autoSub);
            window.removeEventListener('touchstart', autoSub);
        };
        window.addEventListener('click', autoSub);
        window.addEventListener('touchstart', autoSub);

        return app
            .use(plugin)
            .use(ZiggyVue)
            .use(VueTheMask)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
