<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array
});

const roles = {
    0: { name: 'Пользователь', color: 'text-slate-600 bg-slate-100' },
    1: { name: 'Администратор', color: 'text-indigo-600 bg-indigo-50' },
    2: { name: 'Менеджер', color: 'text-emerald-600 bg-emerald-50' }
};

const editingUser = ref(null);
const form = useForm({
    role: ''
});

const startEdit = (user) => {
    editingUser.value = user;
    form.role = user.role;
};

const saveEdit = () => {
    form.patch(route('admin.users.update-role', editingUser.value.id), {
        onSuccess: () => {
            editingUser.value = null;
        }
    });
};
</script>

<template>
    <Head title="Сотрудники" />

    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900">Управление сотрудниками</h1>
            <p class="text-sm text-slate-500 mt-1">Здесь вы можете видеть всех менеджеров и менять их роли</p>
        </template>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Имя</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Роль</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-slate-900">{{ user.name }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">ID: {{ user.id }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ user.username ? '@' + user.username : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span :class="['px-2.5 py-1 rounded-lg text-xs font-bold inline-block', roles[user.role]?.color]">
                                {{ roles[user.role]?.name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button 
                                @click="startEdit(user)"
                                class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Backdrop & Modal -->
        <div v-if="editingUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editingUser = null" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            
            <div class="relative bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-900">Изменение роли: {{ editingUser.name }}</h2>
                    <button @click="editingUser = null" class="text-slate-400 hover:text-slate-600 p-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Выберите роль</label>
                        <select 
                            v-model="form.role" 
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all"
                        >
                            <option value="0">Пользователь (Обычный)</option>
                            <option value="2">Менеджер (Только свои заявки)</option>
                            <option value="1">Администратор (Полный доступ)</option>
                        </select>
                    </div>

                    <button 
                        @click="saveEdit"
                        :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-indigo-200 disabled:opacity-50"
                    >
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
