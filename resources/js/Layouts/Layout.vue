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
</style>
<script>
import {useUsersStore} from "@/stores/users";

export default {
    data() {
        return {
            userStore: useUsersStore(),
            currentTheme: '',
            themes: []
        }
    },
    watch: {},

    computed: {
        tg() {
            return window.Telegram?.WebApp || null;
        },
        isAuthorized() {
            // Разрешаем переход в админку только админам (1) или менеджерам (2)
            if (!this.userStore.self) return false;
            return this.userStore.self.role === 1 || this.userStore.self.role === 2;
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
