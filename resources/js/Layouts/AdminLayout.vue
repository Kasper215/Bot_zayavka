<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router, Head } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);
const pushPermission = ref(null);

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => Number(user.value?.role) >= 2);
const isKasper = computed(() => Number(user.value?.role) === 3);

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
    const nav = [
        { name: 'Заявки', href: '/admin/leads', icon: 'ClipboardDocumentListIcon' },
        { name: 'Оплаты', href: '/admin/payments', icon: 'CreditCardIcon' },
    ];

    if (isAdmin.value) {
        nav.push({ name: 'Сотрудники', href: '/admin/users', icon: 'UserGroupIcon' });
        nav.push({ name: 'Рассылка', href: '/admin/broadcast', icon: 'PaperAirplaneIcon' });
    }

    return nav;
});
</script>

<template>
    <div class="min-h-screen flex bg-[#0F172A] text-slate-200 selection:bg-indigo-500/30 font-sans">
        
        <!-- Background Atmosphere -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-600/10 blur-[120px]"></div>
        </div>

        <aside 
            :class="[
                showingNavigationDropdown ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-[#1E293B]/40 backdrop-blur-2xl border-r border-slate-800/50 transition-transform duration-300 md:translate-x-0 md:static md:w-64'
            ]"
        >
            <div class="p-6 flex flex-col h-full relative z-10">
                <div class="flex items-center gap-4 mb-10 group cursor-pointer" @click="router.visit('/')">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">B</div>
                    <div class="flex flex-col">
                        <span class="font-black text-xl text-white tracking-tight">BioBook</span>
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest leading-none">Admin Lux</span>
                    </div>
                </div>

                <div v-if="user" class="bg-white/5 rounded-3xl p-5 mb-8 border border-white/5 shadow-inner">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold text-lg">
                            {{ user.name?.[0] || 'U' }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="font-bold text-sm truncate text-white">{{ user.name }}</div>
                            <div class="text-[10px] text-indigo-400/80 font-bold uppercase">
                                {{ isKasper ? 'Kasper' : (isAdmin ? 'Admin' : 'Manager') }}
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button v-if="pushPermission !== 'granted'" @click="requestPermission" class="w-full py-2.5 bg-red-500 text-white text-[10px] font-black rounded-xl animate-pulse shadow-lg shadow-red-500/20 uppercase">🔔 Включить уведомления</button>
                        <button v-else @click="registerPush(true)" class="w-full py-2.5 bg-indigo-500/20 text-indigo-300 text-[10px] font-black rounded-xl border border-indigo-400/30 hover:bg-indigo-500/30 transition-colors uppercase">🔔 Уведомления активны</button>
                        <button @click="playAlert" class="w-full py-2.5 bg-white/5 text-slate-300 text-[10px] font-black rounded-xl border border-white/10 hover:bg-white/10 transition-colors uppercase">🔊 Проверить звук</button>
                    </div>
                </div>

                <nav class="space-y-2 flex-1">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href" 
                        class="flex items-center gap-3 px-5 py-4 text-sm font-bold rounded-2xl transition-all duration-300 group"
                        :class="[
                            page.url.startsWith(item.href) 
                            ? 'bg-indigo-500/10 text-white border border-indigo-500/20 shadow-lg shadow-indigo-500/5' 
                            : 'text-slate-400 hover:bg-white/5 hover:text-slate-100'
                        ]"
                    >
                        <div class="w-2 h-2 rounded-full transition-all group-hover:scale-150" :class="page.url.startsWith(item.href) ? 'bg-indigo-400 shadow-[0_0_8px_#818cf8]' : 'bg-slate-600'"></div>
                        {{ item.name }}
                    </Link>
                </nav>

                <div class="mt-auto pt-6 border-t border-slate-800/50">
                    <Link href="/logout" method="post" as="button" class="w-full flex items-center justify-center gap-2 py-4 px-5 text-sm font-bold text-slate-500 hover:text-rose-400 hover:bg-rose-500/5 rounded-2xl transition-all">
                        <span class="text-lg">🚪</span> Выйти из системы
                    </Link>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative z-10">
            <header class="md:hidden flex items-center justify-between p-5 bg-[#1E293B]/80 backdrop-blur-xl border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-xs">B</div>
                    <span class="font-black text-white tracking-tight">BioBook</span>
                </div>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 bg-white/5 rounded-xl text-slate-400 border border-white/10">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                </button>
            </header>
            <main class="flex-1 overflow-y-auto p-4 md:p-10 custom-scrollbar">
                <slot />
            </main>
        </div>
    </div>
</template>
