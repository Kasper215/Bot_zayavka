<template>
    <Head title="Команда | BioBook Lux" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in">
            <div>
                <h1 class="text-4xl font-black text-white tracking-tight">Команда</h1>
                <p class="text-slate-400 mt-2 font-medium">Управление ролями и доступом сотрудников</p>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-slide-up">
            <div 
                v-for="user in users.data" 
                :key="user.id" 
                class="group relative"
            >
                <!-- Selection Glow -->
                <div class="absolute -inset-0.5 bg-gradient-to-r from-transparent via-indigo-500/20 to-transparent rounded-[2.5rem] opacity-0 group-hover:opacity-100 transition duration-500"></div>
                
                <div class="relative bg-[#1E293B]/40 backdrop-blur-xl border border-white/5 hover:border-indigo-500/40 rounded-[2.5rem] p-8 transition-all duration-300 shadow-2xl flex flex-col h-full overflow-hidden shadow-black/20">
                    
                    <!-- Background Glow -->
                    <div 
                        class="absolute -top-12 -right-12 w-32 h-32 rounded-full blur-[45px] opacity-10 transition-colors"
                        :class="[Number(user.role) === 1 ? 'bg-purple-500' : Number(user.role) === 2 ? 'bg-indigo-500' : 'bg-slate-500']"
                    ></div>

                    <div class="flex items-start justify-between mb-8">
                        <!-- Avatar -->
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-indigo-500/20 to-purple-600/20 border border-white/10 flex items-center justify-center text-indigo-400 font-black text-3xl shadow-inner relative overflow-hidden group-hover:scale-105 transition-transform duration-500">
                           <div class="absolute inset-0 bg-indigo-500/5 blur-md opacity-50"></div>
                           <span class="relative">{{ user.name.charAt(0) }}</span>
                        </div>
                        
                        <!-- Role Badge -->
                        <div class="flex flex-col items-end gap-2">
                            <span 
                                :class="[
                                    'px-4 py-1.5 text-[10px] font-black rounded-full border shadow-sm uppercase tracking-widest',
                                    Number(user.role) === 1 
                                        ? 'bg-purple-500/10 text-purple-400 border-purple-500/30 shadow-purple-500/10' 
                                        : Number(user.role) === 2 
                                        ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' 
                                        : 'bg-slate-600/10 text-slate-400 border-slate-600/30'
                                ]"
                            >
                                {{ Number(user.role) === 1 ? 'Админ' : Number(user.role) === 2 ? 'Менеджер' : 'Пользователь' }}
                            </span>
                            <span v-if="user.is_blocked" class="bg-rose-500/20 text-rose-500 border border-rose-500/40 text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                                Banned
                            </span>
                        </div>
                    </div>

                    <div class="mb-8 flex-1">
                        <h3 class="text-2xl font-black text-white mb-2 truncate group-hover:text-indigo-400 transition-colors">{{ user.name }}</h3>
                        <div class="flex items-center gap-2 text-indigo-300/80 font-bold mb-4">
                            <span class="text-sm">@{{ user.username || 'no_user' }}</span>
                            <span class="text-slate-700">·</span>
                            <span class="text-[10px] text-slate-500 uppercase tracking-widest">ID: {{ user.id }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2 text-slate-500 text-[10px] font-black uppercase tracking-widest">
                            <div class="w-2 h-2 rounded-full" :class="user.is_blocked ? 'bg-rose-500 shadow-lg shadow-rose-500/30' : 'bg-emerald-500 shadow-lg shadow-emerald-500/30'"></div>
                            {{ user.is_blocked ? 'Заблокирован' : 'Активен' }}
                        </div>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="pt-6 border-t border-white/5 flex items-center gap-3">
                        <button 
                            @click="toggleRole(user)"
                            class="flex-1 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white py-3.5 px-2 rounded-2xl border border-white/5 transition-all text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 group/btn"
                        >
                            <span class="opacity-50 group-hover/btn:opacity-100 transition-opacity">🔄</span>
                            Роль
                        </button>
                        
                        <button 
                            v-if="!user.is_blocked"
                            @click="blockUser(user)"
                            class="flex-1 bg-[#161E2E]/80 hover:bg-rose-500/20 text-slate-500 hover:text-rose-400 py-3.5 px-2 rounded-2xl border border-white/5 hover:border-rose-500/40 transition-all text-[10px] font-black uppercase tracking-widest"
                        >
                            Блок
                        </button>
                        <button 
                            v-else
                            @click="unblockUser(user)"
                            class="flex-1 bg-emerald-500 hover:bg-emerald-400 text-white py-3.5 px-2 rounded-2xl transition-all text-[10px] font-black uppercase tracking-widest"
                        >
                            Разблок
                        </button>

                        <button 
                            @click="deleteUser(user)"
                            class="w-12 h-12 flex items-center justify-center bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-2xl border border-rose-500/20 transition-all shadow-xl"
                            title="Удалить навсегда"
                        >
                            🗑️
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.links.length > 3" class="mt-12 flex justify-center pb-20">
            <div class="flex p-2 bg-[#1E293B]/60 backdrop-blur-xl border border-white/5 rounded-[2.5rem] gap-1 shadow-2xl">
                <Link 
                    v-for="link in users.links" 
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="min-w-[48px] h-[48px] flex items-center justify-center rounded-2xl text-sm font-black transition-all"
                    :class="[
                        link.active 
                        ? 'bg-indigo-500 text-white shadow-xl shadow-indigo-500/30 scale-105' 
                        : link.url ? 'text-slate-400 hover:bg-white/5 hover:text-white' : 'text-slate-700 pointer-events-none'
                    ]"
                />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    users: Object, // Предполгается пагинация
});

const toggleRole = (user) => {
    if (confirm(`Изменить роль сотруднику ${user.name}?`)) {
        router.post(route('admin.users.toggle-role', user.id));
    }
};

const blockUser = (user) => {
    if (confirm(`Вы уверены, что хотите заблокировать ${user.name}? Доступ в админку будет закрыт.`)) {
        router.post(route('admin.users.block', user.id));
    }
};

const unblockUser = (user) => {
    if (confirm(`Разблокировать ${user.name}?`)) {
        router.post(route('admin.users.unblock', user.id));
    }
};

const deleteUser = (user) => {
    if (confirm(`УДАЛИТЬ сотрудника ${user.name} из системы? Это действие необратимо.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.8s ease-out; }
.animate-slide-up { animation: slideUp 0.6s ease-out; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
