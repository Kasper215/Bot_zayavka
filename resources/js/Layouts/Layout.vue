<script setup>
import {Head} from '@inertiajs/vue3'
import GlobalAlert from "@/Components/GlobalAlert.vue";
import GlobalConfirmModal from "@/Components/GlobalConfirmModal.vue";
import { onMounted, ref } from 'vue';

const isStandalone = ref(false);
const showInstallButton = ref(false);

onMounted(() => {
    // Check if app is already installed/running in standalone
    isStandalone.value = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    
    // Listen for custom event from app.js
    window.addEventListener('pwa-prompt-available', () => {
        if (!isStandalone.value) {
            showInstallButton.value = true;
        }
    });

    // Also check if prompt was already captured before mount
    if (window.pwa?.installPrompt && !isStandalone.value) {
        showInstallButton.value = true;
    }
});

const installPwa = async () => {
    if (window.pwa) {
        const result = await window.pwa.install();
        if (result === 'accepted') {
            showInstallButton.value = false;
        }
    }
};
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
            <button v-if="showInstallButton" @click="installPwa" class="nav-item install-btn">
                <span class="nav-icon animate-bounce">📲</span>
                <span class="nav-label">Скачать</span>
            </button>
            <button v-if="showNotificationButton" @click="requestPushPermission" class="nav-item notify-btn">
                <span class="nav-icon animate-pulse">🔔</span>
                <span class="nav-label">Включить</span>
            </button>
            <a href="/admin" class="nav-item admin-link">
                <span class="nav-icon">🛡️</span>
                <span class="nav-label">Админ</span>
            </a>
        </div>
    </div>
</template>

<style>
/* Global styles to fix white borders and optimize for PWA */
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    background-color: #0f172a; 
    overflow-x: hidden;
    -webkit-tap-highlight-color: transparent;
}

.app-container {
    min-height: 100vh;
    padding-top: env(safe-area-inset-top);
    padding-bottom: 80px; 
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

.notify-btn {
    color: #fbbf24;
}

.install-btn {
    color: #10b981;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.8; }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.animate-pulse {
    animation: pulse 2s infinite ease-in-out;
}

.animate-bounce {
    animation: bounce 1.5s infinite ease-in-out;
}
</style>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    data() {
        return {
            userStore: useUsersStore(),
            showNotificationButton: false
        }
    },
    mounted() {
        this.userStore.fetchPublicSelf();
        this.checkNotificationStatus();
    },
    methods: {
        checkNotificationStatus() {
            if ('Notification' in window) {
                this.showNotificationButton = Notification.permission === 'default';
            }
        },
        async requestPushPermission() {
            if (window.pwa) {
                await window.pwa.registerPush();
                this.checkNotificationStatus();
            }
        },
        scrollTop() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    }
}
</script>

