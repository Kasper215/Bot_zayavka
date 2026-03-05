<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

onMounted(() => {
    if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
        // Применяем цвета из Telegram темы к странице, если они есть
        if (window.Telegram.WebApp.themeParams.bg_color) {
            document.documentElement.style.setProperty('--tg-theme-bg-color', window.Telegram.WebApp.themeParams.bg_color);
        }
    }
});

const showingNavigationDropdown = ref(false);
const page = usePage();

const userRole = computed(() => page.props.auth?.user?.role);
const isAdmin = computed(() => Number(userRole.value) === 1);

const navigation = computed(() => {
    const items = [
        { name: 'Дашборд', href: route('admin.dashboard'), icon: 'HomeIcon', current: route().current('admin.dashboard') },
        { name: 'Заявки', href: route('admin.leads.index'), icon: 'ClipboardDocumentListIcon', current: route().current('admin.leads.*') },
    ];
    
    // Админские разделы
    if (isAdmin.value) {
        items.push({ name: 'Сотрудники', href: route('admin.users.index'), icon: 'UserGroupIcon', current: route().current('admin.users.*') });
        items.push({ name: 'Рассылка', href: route('admin.broadcast.index'), icon: 'PaperAirplaneIcon', current: route().current('admin.broadcast.*') });
    }
    
    return items;
});

const roleLabel = computed(() => {
    return isAdmin.value ? 'Администратор' : 'Менеджер';
});
</script>

<template>
    <div class="min-h-screen flex transition-colors duration-200" style="background-color: var(--tg-theme-secondary-bg-color, #f8fafc);">
        
        <!-- Mobile Sidebar Overlay (Backdrop) -->
        <div 
            v-if="showingNavigationDropdown" 
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm md:hidden transition-opacity"
            @click="showingNavigationDropdown = false"
        ></div>

        <!-- Sidebar Navigation -->
        <aside 
            :class="[
                showingNavigationDropdown ? 'translate-x-0 shadow-2xl' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-72 flex flex-col transition-transform duration-300 md:translate-x-0 md:static md:w-64 border-r'
            ]"
            style="background-color: var(--tg-theme-bg-color, #ffffff); color: var(--tg-theme-text-color, #0f172a); border-color: var(--tg-theme-hint-color, #e2e8f0);"
        >
            <div class="p-6 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold" style="background-color: var(--tg-theme-button-color, #4f46e5); color: var(--tg-theme-button-text-color, #ffffff);">
                        B
                    </div>
                    <span class="font-bold text-xl tracking-tight">BioBook Admin</span>
                </Link>
                <!-- Close btn for mobile -->
                <button @click="showingNavigationDropdown = false" class="md:hidden p-2 rounded-lg opacity-60 hover:opacity-100 transition-opacity">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <nav class="flex-1 px-4 py-2 space-y-2 overflow-y-auto">
                <Link 
                    v-for="item in navigation" 
                    :key="item.name" 
                    :href="item.href"
                    @click="showingNavigationDropdown = false"
                    :class="[
                        item.current 
                        ? 'font-semibold shadow-sm' 
                        : 'opacity-80 hover:opacity-100 hover:bg-black/5',
                        'group flex items-center px-3 py-3 text-sm rounded-xl transition-all duration-200'
                    ]"
                    :style="item.current ? {
                        'background-color': 'var(--tg-theme-button-color, #eff6ff)',
                        'color': 'var(--tg-theme-button-text-color, #4338ca)'
                    } : {}"
                >
                    <svg v-if="item.name === 'Дашборд'" class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <svg v-if="item.name === 'Заявки'" class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 10.5h12M8.25 14.25h12M8.25 18h12" />
                    </svg>
                    <svg v-if="item.name === 'Сотрудники'" class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <svg v-if="item.name === 'Рассылка'" class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User profile footer -->
            <div v-if="$page.props.auth.user" class="border-t p-4 shrink-0" style="border-color: var(--tg-theme-hint-color, #e2e8f0);">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 flex-shrink-0 rounded-full flex items-center justify-center font-medium" style="background-color: var(--tg-theme-secondary-bg-color, #e2e8f0); color: var(--tg-theme-hint-color, #64748b);">
                        {{ $page.props.auth.user.name?.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs truncate opacity-70">{{ roleLabel }}</p>
                    </div>
                    <a 
                        href="/logout" 
                        class="p-2 rounded-lg opacity-70 hover:opacity-100 hover:bg-rose-50 hover:text-rose-600 transition-all"
                        title="Выход"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </a>
                </div>
            </div>
            <div v-else class="p-4 border-t" style="border-color: var(--tg-theme-hint-color, #e2e8f0);">
                <Link href="/login" class="flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors" style="color: var(--tg-theme-button-color, #4f46e5)">
                    Войти в систему
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative z-0">
            <!-- Header for mobile -->
            <header class="md:hidden p-4 sticky top-0 z-30 flex items-center justify-between border-b shadow-sm"
                    style="background-color: var(--tg-theme-bg-color, #ffffff); border-color: var(--tg-theme-hint-color, #e2e8f0); color: var(--tg-theme-text-color, #0f172a);">
                <Link href="/" class="font-bold flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center text-xs" style="background-color: var(--tg-theme-button-color, #4f46e5); color: var(--tg-theme-button-text-color, #ffffff);">
                        B
                    </div>
                    <span class="text-[var(--tg-theme-text-color, #0f172a)]">BioBook</span>
                </Link>
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-lg active:opacity-50 transition-opacity" style="background-color: var(--tg-theme-secondary-bg-color, #f8fafc);">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>

            <main class="flex-1 overflow-y-auto focus:outline-none w-full scroll-smooth pb-12">
                <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                    <header class="mb-6 md:mb-8" v-if="$slots.header">
                        <slot name="header" />
                    </header>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

