<script setup>

import {Head} from '@inertiajs/vue3'
import GlobalAlert from "@/Components/GlobalAlert.vue";
import GlobalConfirmModal from "@/Components/GlobalConfirmModal.vue";
</script>
<template>

    <Head>
        <title>BioBook: Заявка на книгу</title>
        <meta name="description" content="Оформите заявку на написание вашей книги"/>
        <meta name="theme-color" content="#0f172a"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover"/>
    </Head>


    <GlobalAlert></GlobalAlert>
    <GlobalConfirmModal></GlobalConfirmModal>
    <div class="app-container">
        <slot/>
    </div>


    <div class="bottom-nav">
        <div class="nav-content">
            <button @click="scrollTop" class="nav-item">
                <span class="nav-icon">▲</span>
                <span class="nav-label">Наверх</span>
            </button>
            <a href="/admin" class="nav-item admin-link">
                <span class="nav-icon">🛡️</span>
                <span class="nav-label">Админ</span>
            </a>
        </div>
    </div>

    <!-- PC Neat Install Button (Hidden on strict mobile) -->
    <button v-if="deferredPrompt && !isPwa && !isTelegram" @click="showInstallBanner = true" class="pc-neat-install-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zm-9 13.5a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5V16.5a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V16.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
        </svg>
        Установить
    </button>

    <!-- PWA Install Banner (Modal style) -->
    <Transition name="fade-slide">
        <div v-if="showInstallBanner" class="pwa-modal-overlay">
            <div class="pwa-modal">
                <div class="pwa-header">
                    <h3>Установить приложение</h3>
                    <button @click="dismissInstall" class="pwa-close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="pwa-body">
                    Вы можете установить BioBook как приложение и запускать его прямо с рабочего стола.
                </div>
                
                <div class="pwa-footer">
                    <button @click="dismissInstall" class="pwa-btn-cancel">Позже</button>
                    <button @click="installApp" class="pwa-btn-install">Установить</button>
                </div>
            </div>
        </div>
    </Transition>

</template>

<style>
/* Global styles to fix white borders and optimize for PWA */
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    background-color: #0f172a; /* Matches the design background */
    overflow-x: hidden;
    -webkit-tap-highlight-color: transparent;
}

.app-container {
    min-height: 100vh;
    padding-bottom: 80px; /* Space for the bottom nav */
}
</style>

<style scoped>
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: rgba(15, 23, 42, 0.9);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding-bottom: env(safe-area-inset-bottom);
}

.nav-content {
    display: flex;
    justify-content: space-around;
    align-items: center;
    width: 100%;
    max-width: 600px;
}

.nav-item {
    background: none;
    border: none;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.nav-item:hover {
    color: #60a5fa;
    transform: translateY(-2px);
}

.nav-icon {
    font-size: 1.25rem;
}

.nav-label {
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.admin-link {
    color: rgba(255, 255, 255, 0.4);
}

.admin-link:hover {
    color: #c084fc;
}

/* PWA Modal Styles matching the screenshot */
.pwa-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
}

.pwa-modal {
    background: #ffffff;
    border-radius: 6px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    transform: translateY(0);
}

.pwa-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.pwa-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.pwa-close-btn {
    background: #eff6ff;
    border: 2px solid #bfdbfe;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #000;
    transition: background 0.2s;
}

.pwa-close-btn:hover {
    background: #dbeafe;
}

.pwa-close-btn svg {
    width: 20px;
    height: 20px;
}

.pwa-body {
    padding: 20px;
    color: #4b5563;
    font-size: 0.95rem;
    line-height: 1.5;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.pwa-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 20px;
    background: #ffffff;
}

.pwa-btn-cancel {
    background: #f3f4f6;
    border: 1px solid #9ca3af;
    color: #374151;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}

.pwa-btn-cancel:hover {
    background: #e5e7eb;
}

.pwa-btn-install {
    background: #60a5fa;
    border: 1px solid #3b82f6;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}

.pwa-btn-install:hover {
    background: #3b82f6;
}

/* Animations */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 0.3s ease;
}

.fade-slide-enter-active .pwa-modal,
.fade-slide-leave-active .pwa-modal {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from {
    opacity: 0;
}

.fade-slide-enter-from .pwa-modal {
    transform: translateY(30px) scale(0.95);
}

.fade-slide-leave-to {
    opacity: 0;
}

.fade-slide-leave-to .pwa-modal {
    transform: translateY(30px) scale(0.95);
}

.pc-neat-install-btn {
    display: none;
}

@media (min-width: 640px) {
    .pc-neat-install-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        position: fixed;
        top: 20px;
        right: 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.2s ease;
    }
    
    .pc-neat-install-btn svg {
        width: 18px;
        height: 18px;
        color: #3b82f6;
    }
    
    .pc-neat-install-btn:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
}
</style>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    data() {
        return {
            userStore: useUsersStore(),
            currentTheme: '',
            themes: [],
            deferredPrompt: null,
            showInstallBanner: false
        }
    },
    watch: {},

    computed: {
        tg() {
            return window.Telegram?.WebApp || null;
        },
        isTelegram() {
            return window.Telegram?.WebApp?.platform && window.Telegram.WebApp.platform !== 'unknown';
        },
        isAuthorized() {
            // Разрешаем переход в админку только админам (1) или менеджерам (2)
            if (!this.userStore.self) return false;
            return this.userStore.self.role === 1 || this.userStore.self.role === 2;
        },
        isPwa() {
            return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        }
    },


    mounted() {
        this.userStore.fetchPublicSelf();

        if (this.tg) {
            this.tg.expand();
            if (this.tg.BackButton) {
                this.tg.BackButton.hide();
            }
        }

        if (window.deferredPrompt) {
            this.deferredPrompt = window.deferredPrompt;
            if (!this.isPwa && !this.isTelegram && !localStorage.getItem('pwa_dismissed')) {
                setTimeout(() => { this.showInstallBanner = true; }, 1500);
            }
        }

        // Listen for future firings
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            window.deferredPrompt = e;
            
            if (!this.isPwa && !this.isTelegram && !localStorage.getItem('pwa_dismissed')) {
                setTimeout(() => { this.showInstallBanner = true; }, 1500);
            }
        });

        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            this.showInstallBanner = false;
            console.log('PWA was installed');
        });
    },

    methods: {

        goTo(name) {
            this.$router.push({name: name})
        },

        scrollTop() {
            window.scrollTo(0, 80);
        },
        openLink(url) {
            this.tg.openLink(url, {
                try_instant_view: true
            })
        },

        async installApp() {
             if (!this.deferredPrompt) {
                 this.showInstallBanner = false;
                 return;
             }
            
            this.showInstallBanner = false;
            // Show the prompt
            this.deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log(`User response to the install prompt: ${outcome}`);
            // We've used the prompt, and can't use it again, throw it away
            this.deferredPrompt = null;
        },

        dismissInstall() {
            this.showInstallBanner = false;
            localStorage.setItem('pwa_dismissed', 'true');
        }


    },



}
</script>

<style>
.fixed-top-menu {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #ffffff;
}
</style>
