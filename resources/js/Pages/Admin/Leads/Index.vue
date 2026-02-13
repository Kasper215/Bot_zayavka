<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

let debounceTimer;

const props = defineProps({
    leads: Object,
    filters: Object,
    userRole: [String, Number],
    managers: Array
});

const statuses = {
    new: { label: 'Новая', class: 'bg-blue-50 text-blue-700 border-blue-100' },
    in_progress: { label: 'В работе', class: 'bg-amber-50 text-amber-700 border-amber-100' },
    rejected: { label: 'Отказ', class: 'bg-rose-50 text-rose-700 border-rose-100' },
    completed: { label: 'Завершена', class: 'bg-emerald-50 text-emerald-700 border-emerald-100' },
};

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const updateFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.leads.index'), {
            search: search.value,
            status: statusFilter.value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
};

watch([search, statusFilter], () => {
    updateFilters();
});

const editingLead = ref(null);
const form = useForm({
    status: '',
    manager_notes: '',
    manager_id: '',
});

const startEdit = (lead) => {
    editingLead.value = lead;
    form.status = lead.status;
    form.manager_notes = lead.manager_notes || '';
    form.manager_id = lead.manager_id || '';
};

const saveEdit = () => {
    form.put(route('admin.leads.update', editingLead.value.id), {
        onSuccess: () => {
            editingLead.value = null;
        }
    });
};

const clearAllLeads = () => {
    if (confirm('Вы уверены, что хотите удалить ВСЕ заявки? Это действие необратимо!')) {
        router.delete(route('admin.leads.destroy-all'), {
            onSuccess: () => {
                alert('Все заявки успешно удалены');
            }
        });
    }
};
</script>

<template>
    <Head title="Управление заявками" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Заявки</h1>
                    <p class="text-sm text-slate-500 mt-1">Управление всеми обращениями из Telegram бота</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Поиск..." 
                            class="pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full md:w-64 transition-all duration-200"
                        >
                    </div>

                    <select 
                        v-model="statusFilter"
                        class="border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                    >
                        <option value="">Все статусы</option>
                        <option value="new">Новые</option>
                        <option value="in_progress">В работе</option>
                        <option value="rejected">Отказ</option>
                        <option value="completed">Завершены</option>
                    </select>

                    <a 
                        :href="route('admin.leads.export', { search, status: statusFilter })"
                        class="px-4 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 rounded-xl border border-indigo-200 transition-all duration-200 flex items-center gap-2"
                        title="Скачать Excel"
                    >
                        📊 Excel
                    </a>
                    
                    <button 
                        v-if="Number(userRole) === 1"
                        @click="clearAllLeads"
                        class="px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 rounded-xl border border-rose-200 transition-all duration-200"
                        title="Очистить все заявки"
                    >
                        🗑️ Очистить всё
                    </button>
                </div>
            </div>
        </template>

        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Клиент</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Услуга / Объем</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Контакты</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Действие</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50 transition-colors duration-150 group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-semibold border border-slate-200">
                                        {{ lead.user?.name ? lead.user.name.charAt(0) : '?' }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">
                                            {{ lead.client_name || lead.user?.name || 'Н/Д' }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            <span v-if="lead.client_name && lead.user?.name && lead.client_name !== lead.user.name" class="mr-1">
                                                ({{ lead.user.name }})
                                            </span>
                                            @{{ lead.user?.username || 'no_username' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-medium text-slate-900 capitalize">{{ lead.service_type }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 line-clamp-1 max-w-[200px]">{{ lead.volume_stage }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm text-slate-600">{{ lead.contacts }}</div>
                                <div class="text-[10px] text-slate-400 mt-1">{{ new Date(lead.created_at).toLocaleString('ru-RU') }}</div>
                                <div v-if="lead.manager" class="mt-2 flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded-full bg-indigo-100 flex items-center justify-center text-[8px] text-indigo-600 font-bold">M</div>
                                    <span class="text-[10px] text-slate-500">Отв: {{ lead.manager.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span 
                                    :class="[
                                        'px-2.5 py-1 text-xs font-semibold rounded-lg border',
                                        statuses[lead.status]?.class || 'bg-slate-100 text-slate-600 border-slate-200'
                                    ]"
                                >
                                    {{ statuses[lead.status]?.label || lead.status }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <button 
                                    @click="startEdit(lead)"
                                    class="text-slate-400 hover:text-indigo-600 p-2 rounded-lg hover:bg-indigo-50 transition-all duration-200"
                                    title="Редактировать"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="leads.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                Заявки не найдены
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div v-if="leads.links.length > 3" class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    Показано <span class="font-medium text-slate-700">{{ leads.from }}</span> - <span class="font-medium text-slate-700">{{ leads.to }}</span> из <span class="font-medium text-slate-700">{{ leads.total }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <Link 
                        v-for="link in leads.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        :data="{ search: search, status: statusFilter }"
                        :preserve-state="true"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200"
                        :class="[
                            link.active 
                            ? 'bg-indigo-600 text-white shadow-sm' 
                            : link.url ? 'text-slate-600 hover:bg-white hover:shadow-sm border border-transparent' : 'text-slate-300 pointer-events-none'
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Модальное окно -->
        <div v-if="editingLead" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editingLead = null" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            
            <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl transform transition-all overflow-hidden border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Заявка #{{ editingLead.id }}</h2>
                        <p class="text-xs text-slate-500">
                            {{ editingLead.client_name || editingLead.user?.name || 'Без имени' }}
                            <span v-if="editingLead.client_name && editingLead.user?.name && editingLead.client_name !== editingLead.user.name">
                                ({{ editingLead.user.name }})
                            </span>
                            {{ editingLead.user?.username ? ' @' + editingLead.user.username : ' (no tg)' }}
                        </p>
                    </div>
                    <button @click="editingLead = null" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-5">
                    <div v-if="Number(userRole) === 1">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Назначить менеджера</label>
                        <select 
                            v-model="form.manager_id" 
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                        >
                            <option value="">Не назначен</option>
                            <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                                {{ manager.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Статус обработки</label>
                        <select 
                            v-model="form.status" 
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                        >
                            <option value="new">Новая</option>
                            <option value="in_progress">В работе</option>
                            <option value="rejected">Отказ</option>
                            <option value="completed">Завершена</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                            Заметки менеджера
                            <span class="text-[10px] text-slate-400 font-normal">Видны только в админке</span>
                        </label>
                        <textarea 
                            v-model="form.manager_notes" 
                            rows="5" 
                            placeholder="Введите подробности по заявке..."
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                        ></textarea>
                    </div>
                </div>

                <div class="p-6 bg-slate-50/80 flex justify-end gap-3 border-t border-slate-100">
                    <button @click="editingLead = null" class="px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-white rounded-xl border border-slate-200 transition-all duration-200">
                        Отмена
                    </button>
                    <button 
                        @click="saveEdit" 
                        :disabled="form.processing" 
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-100 disabled:opacity-50 transition-all duration-200"
                    >
                        {{ form.processing ? 'Сохранение...' : 'Обновить информацию' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

