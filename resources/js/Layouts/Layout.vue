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
</style>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    data() {
        return {
            userStore: useUsersStore(),
        }
    },
    mounted() {
        this.userStore.fetchPublicSelf();
        
        // Автоматическая подписка на пуши при взаимодействии
        const registerOnInteraction = () => {
            this.registerPush();
            window.removeEventListener('click', registerOnInteraction);
            window.removeEventListener('touchstart', registerOnInteraction);
        };
        
        window.addEventListener('click', registerOnInteraction);
        window.addEventListener('touchstart', registerOnInteraction);
    },
    methods: {
        scrollTop() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
        async registerPush() {
            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
                
                const registration = await navigator.serviceWorker.ready;
                const permission = await Notification.requestPermission();
                
                if (permission !== 'granted') return;

                const vapidPublicKey = this.$page.props.vapid_public_key;
                if (!vapidPublicKey) return;

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });

                await axios.post(route('notifications.subscribe'), subscription);
                console.log('PWA: Push subscription successful');
            } catch (e) {
                console.error('PWA: Push subscription failed', e);
            }
        },
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    }
}
</script>
