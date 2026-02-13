<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

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
    <div class="min-h-screen bg-slate-50 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col">
            <div class="p-6">
                <Link href="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold">B</span>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900">BioBook Admin</span>
                </Link>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <Link 
                    v-for="item in navigation" 
                    :key="item.name" 
                    :href="item.href"
                    :class="[
                        item.current 
                        ? 'bg-indigo-50 text-indigo-700' 
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200'
                    ]"
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

            <div v-if="$page.props.auth.user" class="flex items-center justify-between border-t border-slate-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-medium">
                        {{ $page.props.auth.user.name?.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs truncate" :class="isAdmin ? 'text-indigo-600' : 'text-slate-500'">{{ roleLabel }}</p>
                    </div>
                </div>
                <a 
                    href="/logout" 
                    class="text-slate-400 hover:text-rose-600 transition-all p-2 hover:bg-rose-50 rounded-lg"
                    title="Выход"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                </a>
            </div>
            <div v-else class="p-4 border-t border-slate-100">
                <Link href="/login" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">
                    Войти в систему
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header for mobile -->
            <header class="bg-white border-b border-slate-200 md:hidden p-4">
                <div class="flex items-center justify-between">
                    <Link href="/" class="font-bold text-indigo-600">BioBook</Link>
                    <button class="p-2 text-slate-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto focus:outline-none">
                <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                    <header class="mb-8" v-if="$slots.header">
                        <slot name="header" />
                    </header>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

