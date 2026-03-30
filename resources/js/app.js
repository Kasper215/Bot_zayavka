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

// ─── FCM-free Notification Polling (работает в России без VPN) ───────────────

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}

const deviceToken = localStorage.getItem('biobook_device_token') || (() => {
    const t = generateUUID();
    localStorage.setItem('biobook_device_token', t);
    return t;
})();

// Регистрируем устройство на сервере
async function registerDevice() {
    try {
        await fetch('/api/device/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ token: deviceToken }),
        });
        console.log('Device: ✅ Registered token', deviceToken.substring(0, 8) + '...');
    } catch (e) {
        console.warn('Device: register failed', e.message);
    }
}

// Опрашиваем сервер на предмет новых уведомлений
async function pollNotifications() {
    try {
        const resp = await fetch(`/api/device/poll?token=${deviceToken}`);
        if (!resp.ok) return;
        const { notifications } = await resp.json();

        for (const n of notifications) {
            if ('serviceWorker' in navigator && Notification.permission === 'granted') {
                const reg = await navigator.serviceWorker.ready;
                reg.showNotification(n.title, {
                    body: n.body,
                    icon: n.icon || '/pwa-icon.png',
                    badge: '/pwa-icon.png',
                    data: { url: n.url || '/' },
                    vibrate: [200, 100, 200],
                });
            }
        }

        if (notifications.length > 0) {
            console.log(`Device: 🔔 Received ${notifications.length} notification(s)`);
        }
    } catch (e) {
        // Silent fail — не мешаем работе приложения
    }
}

// Запускаем после загрузки
window.addEventListener('load', async () => {
    await registerDevice();
    // Первый опрос через 5 секунд, затем каждые 30 секунд
    setTimeout(async () => {
        await pollNotifications();
        setInterval(pollNotifications, 30_000);
    }, 5000);
});

// ─── PWA Infrastructure ───────────────────────────────────────────────────────

let deferredPrompt = null;

// 1) Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
                console.log('PWA: ✅ SW Registered');
            })
            .catch(err => {
                console.error('PWA: ❌ SW Registration failed:', err);
            });
    });
}

// 2) Capture install prompt
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    console.log('PWA: 📥 Install prompt captured');
    showInstallOverlay();
});

// 3) Timeout for manual instruction if prompt is blocked (common in Russia)
setTimeout(() => {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (!isStandalone && !document.getElementById('pwa-install-overlay')) {
        showInstallOverlay();
    }
}, 3000);

function showInstallOverlay() {
    if (document.getElementById('pwa-install-overlay')) return;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (isStandalone) return;

    const overlay = document.createElement('div');
    overlay.id = 'pwa-install-overlay';
    overlay.style = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 10000; display: flex; align-items: flex-end; padding: 24px; font-family: system-ui, -apple-system, sans-serif;';
    
    overlay.innerHTML = `
        <div style="background: #111827; width: 100%; border-radius: 28px; padding: 32px 24px; color: white; box-shadow: 0 -10px 50px rgba(0,0,0,0.6); position: relative; animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
            <button id="pwa-close-btn" style="position: absolute; top: 20px; right: 20px; background: #374151; border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px;">✕</button>
            
            <div style="text-align: center; margin-bottom: 28px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 20px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 36px; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);">✨</div>
                <h3 style="margin: 0 0 10px; font-size: 22px; font-weight: 700;">Установить BioBook</h3>
                <p style="margin: 0; opacity: 0.8; font-size: 15px; line-height: 1.5; padding: 0 20px;">Лучший опыт работы: мгновенные пуши, работа в фоне и без VPN</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                ${isIOS ? `
                    <div style="background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); text-align: center;">
                        <p style="margin: 0; font-size: 14px; line-height: 1.6;">
                            Нажмите <span style="background: #3b82f6; padding: 2px 8px; border-radius: 6px; font-weight: 600;">Поделиться</span><br>
                            затем <span style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 6px; font-weight: 600;">На экран Домой</span>
                        </p>
                    </div>
                ` : `
                    <a href="/downloads/biobook.apk" style="background: #3b82f6; color: white; text-decoration: none; padding: 18px; border-radius: 18px; font-weight: 700; font-size: 16px; text-align: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: transform 0.2s;">
                        📦 Скачать APK (Рекомендуется)
                    </a>
                    
                    <div style="display: flex; align-items: center; gap: 15px; margin: 5px 0;">
                        <hr style="flex: 1; opacity: 0.2;"> <span style="font-size: 12px; opacity: 0.5;">ИЛИ</span> <hr style="flex: 1; opacity: 0.2;">
                    </div>

                    <button id="pwa-install-btn" style="background: transparent; border: 1.5px solid #374151; color: white; padding: 14px; border-radius: 18px; font-weight: 600; font-size: 14px; cursor: pointer;">
                        Браузерная установка
                    </button>
                `}
            </div>
            
            <style>
                @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
            </style>
        </div>
    `;
    
    document.body.appendChild(overlay);

    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn && deferredPrompt) {
        installBtn.onclick = async () => {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (outcome === 'accepted') overlay.remove();
        };
    } else if (installBtn) {
        installBtn.onclick = () => {
            alert('Для этой установки нажмите три точки в меню браузера и выберите "Добавить на главный экран"');
        };
    }

    document.getElementById('pwa-close-btn').onclick = () => overlay.remove();
}

window.addEventListener('appinstalled', () => {
    console.log('PWA: 🎉 App installed!');
    deferredPrompt = null;
    const overlay = document.getElementById('pwa-install-overlay');
    if (overlay) overlay.remove();
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

        // ───── APK Update Checker ────────────────────────────────────────────────────

async function checkApkUpdate() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if (!isStandalone) return;

    try {
        const response = await fetch('/version.json?t=' + Date.now());
        const remote = await response.json();
        const localVersion = localStorage.getItem('biobook_apk_version') || '1.0.0';

        if (remote.version !== localVersion) {
            if (confirm(`Доступно обновление приложения (${remote.version}). Скачать новую версию?`)) {
                localStorage.setItem('biobook_apk_version', remote.version);
                window.location.href = remote.url;
            }
        }
    } catch (e) {
        // Silent fail
    }
}

// Проверяем обновления при загрузке
window.addEventListener('load', () => {
    setTimeout(checkApkUpdate, 5000);
});

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
