<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router, Head } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);
const pushPermission = ref(null);

const user = computed(() => page.props.auth?.user);
const isKasper = computed(() => user.value?.role === 3);

// Используем localStorage, чтобы ID синхронизировался МЕЖДУ вкладками
const lastLeadId = ref(localStorage.getItem('last_lead_id') ? parseInt(localStorage.getItem('last_lead_id')) : null);
const audio = ref(null);
let pollingRef = null;

const checkNewLeads = async () => {
    try {
        const response = await fetch('/admin/leads/check-new');
        if (!response.ok) return;
        const data = await response.json();
        
        // Получаем актуальный ID из localStorage (другая вкладка могла обновить его)
        const currentStoredId = localStorage.getItem('last_lead_id') ? parseInt(localStorage.getItem('last_lead_id')) : null;
        
        // Только если это не первая загрузка И ID действительно НОВЕЕ чем в памяти И в localStorage
        if (lastLeadId.value !== null && currentStoredId !== null && data.latest_id > currentStoredId) {
            // Оставляем ТОЛЬКО ЗВУК для оперативности
            if (audio.value) audio.value.play().catch(() => {});
        }
        lastLeadId.value = data.latest_id;
        localStorage.setItem('last_lead_id', data.latest_id.toString());
    } catch (e) {
        console.error('Lead check failed', e);
    }
};

const playAlert = () => {
    if (audio.value) audio.value.play().catch(e => console.log('Audio blocked', e));
};

// Функция для конвертации VAPID ключа
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Регистрация подписки на сервере
const registerPush = async (showFeedback = false) => {
    try {
        if (!('serviceWorker' in navigator)) return;
        
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(page.props.vapid_public_key)
        });
        
        const subData = subscription.toJSON();
        
        const response = await fetch('/admin/notifications/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token
            },
            body: JSON.stringify({
                endpoint: subData.endpoint,
                keys: subData.keys
            })
        });

        if (response.ok && showFeedback) {
            console.log('✅ Push registered successfully');
        }
    } catch (e) {
        console.error('Push registration error:', e);
    }
};

const requestPermission = async () => {
    const perm = await Notification.requestPermission();
    pushPermission.value = perm;
    if (perm === 'granted') {
        playAlert();
        await registerPush();
    }
};

onMounted(async () => {
    // Инициализация аудио
    audio.value = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
    
    if (typeof Notification !== 'undefined') {
        pushPermission.value = Notification.permission;
        // Если уже разрешено - пробуем зарегаться в фоне (для новых устройств)
        if (Notification.permission === 'granted') {
            registerPush();
        }
    }
    
    // Начальная установка ID без уведомления
    try {
        const response = await fetch('/admin/leads/check-new');
        if (response.ok) {
            const data = await response.json();
            lastLeadId.value = data.latest_id;
            localStorage.setItem('last_lead_id', data.latest_id.toString());
        }
    } catch (e) {}

    // Запускаем ОДИН таймер
    pollingRef = setInterval(checkNewLeads, 30000);
});

onUnmounted(() => {
    if (pollingRef) clearInterval(pollingRef);
});

const navigation = computed(() => {
    return [
        { name: 'Заявки', href: '/admin/leads', icon: 'ClipboardDocumentListIcon' },
        { name: 'Сотрудники', href: '/admin/users', icon: 'UserGroupIcon' },
        { name: 'Рассылка', href: '/admin/broadcast', icon: 'PaperAirplaneIcon' },
    ];
});
</script>

<template>
    <div class="min-h-screen flex bg-[#0F172A] text-slate-200">
        <aside 
            :class="[
                showingNavigationDropdown ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-[#1E293B] border-r border-slate-800 transition-transform duration-300 md:translate-x-0 md:static md:w-64'
            ]"
        >
            <div class="p-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white">B</div>
                    <span class="font-bold text-xl text-white">BioBook</span>
                </div>

                <div v-if="user" class="bg-slate-800/50 rounded-2xl p-4 mb-6 border border-slate-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
                            {{ user.name?.[0] || 'U' }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="font-bold text-sm truncate text-white">{{ user.name }}</div>
                            <div class="text-[10px] text-indigo-400 font-medium uppercase">{{ isKasper ? 'Kasper' : 'Staff' }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button v-if="pushPermission !== 'granted'" @click="requestPermission" class="w-full py-2 bg-red-600 text-white text-[10px] font-bold rounded-lg animate-pulse">🔔 ВКЛЮЧИТЬ УВЕДОМЛЕНИЯ</button>
                        <button v-else @click="registerPush(true)" class="w-full py-2 bg-indigo-600/30 text-indigo-100 text-[10px] font-bold rounded-lg border border-indigo-500/50">🔔 ПРОВЕРИТЬ УВЕДОМЛЕНИЯ</button>
                        <button @click="playAlert" class="w-full py-2 bg-slate-700 text-slate-100 text-[10px] font-bold rounded-lg border border-slate-600">🔊 ПРОВЕРИТЬ ЗВУК</button>
                    </div>
                </div>

                <nav class="space-y-1">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href" class="block px-4 py-3 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-slate-100 rounded-xl">
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
            <div class="mt-auto p-6 border-t border-slate-800">
                <a href="/logout" class="text-slate-400 hover:text-rose-400 text-sm font-medium flex items-center gap-2">Выйти</a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="md:hidden flex items-center justify-between p-4 bg-[#1E293B]">
                <span class="font-bold text-white">BioBook</span>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-slate-400">Меню</button>
            </header>
            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
