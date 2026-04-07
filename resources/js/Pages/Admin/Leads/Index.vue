<template>
    <Head title="Управление заявками | BioBook Lux" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in">
            <div>
                <h1 class="text-4xl font-black text-white tracking-tight">Заявки</h1>
                <p class="text-slate-400 mt-2 font-medium">Контроль и управление всеми обращениями системы</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-300"></div>
                    <div class="relative flex items-center bg-[#1E293B]/60 backdrop-blur-xl border border-white/5 rounded-2xl overflow-hidden">
                        <span class="pl-4 text-slate-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Поиск по имени или контактам..." 
                            class="bg-transparent border-none text-white text-sm py-3.5 pl-3 pr-10 focus:ring-0 w-full md:w-80 placeholder:text-slate-600 font-medium"
                        >
                    </div>
                </div>

                <select 
                    v-model="statusFilter"
                    class="bg-[#1E293B]/60 backdrop-blur-xl border border-white/5 rounded-2xl text-white text-sm py-3.5 px-6 focus:ring-2 focus:ring-indigo-500 transition-all font-bold cursor-pointer outline-none"
                >
                    <option value="" class="bg-[#1E293B]">Все статусы</option>
                    <option value="new" class="bg-[#1E293B]">Новые</option>
                    <option value="pending_payment" class="bg-[#1E293B]">Ожидают оплаты</option>
                    <option value="awaiting_confirmation" class="bg-[#1E293B]">Проверка оплаты</option>
                    <option value="paid" class="bg-[#1E293B]">Оплачено</option>
                    <option value="in_progress" class="bg-[#1E293B]">В работе</option>
                    <option value="rejected" class="bg-[#1E293B]">Отказ</option>
                    <option value="completed" class="bg-[#1E293B]">Завершены</option>
                </select>

                <div class="flex items-center gap-2">
                    <a 
                        :href="route('admin.leads.export', { search, status: statusFilter })"
                        class="bg-white/5 hover:bg-white/10 text-white p-3.5 rounded-2xl border border-white/10 transition-all shadow-xl"
                        title="Экспорт в Excel"
                    >
                        📊
                    </a>
                    <button 
                        v-if="Number(userRole) === 1"
                        @click="clearAllLeads"
                        class="bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white p-3.5 rounded-2xl border border-rose-500/20 transition-all flex items-center justify-center"
                        title="Очистить всё"
                    >
                        🗑️
                    </button>
                </div>
            </div>
        </div>

        <!-- Leads Content -->
        <div class="grid grid-cols-1 gap-4 animate-slide-up">
            <div 
                v-for="lead in leads.data" 
                :key="lead.id" 
                @click="startEdit(lead)"
                class="group relative"
            >
                <div class="absolute -inset-0.5 bg-gradient-to-r from-transparent via-indigo-500/10 to-transparent rounded-[2rem] opacity-0 group-hover:opacity-100 transition duration-500"></div>
                
                <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between bg-[#1E293B]/40 backdrop-blur-xl border border-white/5 hover:border-indigo-500/30 rounded-[2rem] p-6 lg:p-8 cursor-pointer transition-all duration-300 shadow-2xl overflow-hidden shadow-black/20">
                    
                    <div class="flex items-center gap-6 flex-1 min-w-0">
                        <!-- Avatar Shield -->
                        <div class="flex-shrink-0 w-16 h-16 rounded-3xl bg-gradient-to-br from-indigo-500/10 to-purple-600/10 border border-white/5 flex items-center justify-center text-indigo-400 font-black text-2xl shadow-inner relative">
                           <div class="absolute inset-2 bg-indigo-500/5 rounded-2xl blur-sm scale-110"></div>
                           <span class="relative">{{ lead.client_name ? lead.client_name.charAt(0) : '?' }}</span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4 mb-1">
                                <h3 class="text-xl font-black text-white truncate">{{ lead.client_name || lead.user?.name || 'Н/Д' }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] bg-white/5 text-slate-400 py-1 px-3 rounded-full font-bold uppercase tracking-widest border border-white/5">#{{ lead.id }}</span>
                                    <span 
                                        :class="[
                                            'px-3 py-1 text-[10px] font-black rounded-full border shadow-sm uppercase tracking-widest',
                                            statuses[lead.status]?.class || 'bg-slate-500/10 text-slate-400 border-slate-500/30'
                                        ]"
                                    >
                                        {{ statuses[lead.status]?.label || lead.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                                <span class="text-indigo-400/80">@{{ lead.user?.username || 'no_id' }}</span>
                                <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                                <span class="truncate">{{ lead.service_type }}</span>
                                <span v-if="lead.manager" class="flex items-center gap-1 ml-2 text-emerald-400/80">
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                    Отв: {{ lead.manager.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 lg:mt-0 lg:ml-10 flex flex-col lg:items-end gap-3 text-right">
                        <div class="flex flex-col lg:items-end">
                            <div class="text-white font-bold mb-1 truncate max-w-[300px]">{{ lead.contacts }}</div>
                            <div class="text-xs text-slate-600 font-bold uppercase tracking-widest">
                                {{ new Date(lead.created_at).toLocaleDateString() }} · {{ new Date(lead.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Hover Reveal -->
                    <div class="absolute right-8 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity hidden lg:block">
                        <button class="w-12 h-12 rounded-2xl bg-indigo-500 text-white shadow-xl shadow-indigo-500/20 flex items-center justify-center hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </div>

                </div>
            </div>

            <div v-if="leads.data.length === 0" class="py-32 text-center animate-pulse">
                <div class="text-5xl mb-6 opacity-30">📭</div>
                <h3 class="text-xl font-bold text-slate-600 uppercase tracking-[0.2em]">Заявок пока нет</h3>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="leads.links.length > 3" class="mt-12 flex justify-center pb-20">
            <div class="flex p-2 bg-[#1E293B]/60 backdrop-blur-xl border border-white/5 rounded-[2rem] gap-1 shadow-2xl">
                <Link 
                    v-for="link in leads.links" 
                    :key="link.label"
                    :href="link.url || '#'"
                    :data="{ search: search, status: statusFilter }"
                    v-html="link.label"
                    class="min-w-[44px] h-[44px] flex items-center justify-center rounded-2xl text-sm font-black transition-all"
                    :class="[
                        link.active 
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
                        : link.url ? 'text-slate-400 hover:bg-white/5 hover:text-white' : 'text-slate-700 pointer-events-none'
                    ]"
                />
            </div>
        </div>

        <!-- Edit Modal Lux -->
        <transition name="modal">
            <div v-if="editingLead" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div @click="editingLead = null" class="absolute inset-0 bg-[#0F172A]/90 backdrop-blur-md"></div>
                
                <div class="relative bg-[#1E293B] border border-white/10 rounded-[3rem] w-full max-w-4xl shadow-2xl flex flex-col md:flex-row overflow-hidden max-h-[90vh]">
                    
                    <!-- Left Column (Details) -->
                    <div class="md:w-5/12 p-10 bg-[#161E2E]/50 border-r border-white/5 overflow-y-auto">
                        <div class="mb-10">
                            <h2 class="text-3xl font-black text-white mb-2">Заявка #{{ editingLead.id }}</h2>
                            <span class="inline-block px-4 py-1.5 rounded-full bg-indigo-500/10 text-indigo-400 text-[10px] font-black uppercase tracking-[0.15em] border border-indigo-500/20">
                                {{ statuses[editingLead.status]?.label }}
                            </span>
                        </div>

                        <div class="space-y-8">
                            <div class="item">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-2">Клиент</span>
                                <div class="text-xl font-black text-white leading-none mb-2">{{ editingLead.client_name || editingLead.user?.name || 'Н/Д' }}</div>
                                <div class="flex flex-col gap-1">
                                    <div class="text-indigo-400 font-bold text-sm flex items-center gap-2">
                                        <span class="opacity-50">📱</span> {{ editingLead.contacts }}
                                    </div>
                                    <div v-if="editingLead.user?.username" class="text-slate-500 text-[10px] font-medium flex items-center gap-2">
                                        <span class="opacity-50 text-[12px]">@</span>{{ editingLead.user?.username }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-2">Услуга / Проект</span>
                                <div class="text-white font-bold mb-1">{{ editingLead.service_type }}</div>
                                <div class="p-4 bg-white/5 border border-white/5 rounded-2xl">
                                    <p class="text-slate-400 text-xs italic leading-relaxed">{{ editingLead.volume_stage }}</p>
                                    <div v-if="editingLead.calc_price" class="mt-3 pt-3 border-t border-white/5 flex items-center justify-between">
                                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Оценка:</span>
                                        <span class="text-emerald-400 font-black text-sm">{{ editingLead.calc_price }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="editingLead.extra" class="item">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-2">Описание / Идея</span>
                                <div class="p-4 bg-white/5 border border-white/5 rounded-2xl text-slate-300 text-sm leading-relaxed whitespace-pre-wrap font-medium">
                                    {{ editingLead.extra }}
                                </div>
                            </div>

                            <div class="item">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-3">Файлы ({{ parseFiles(editingLead.files).length }})</span>
                                <div class="grid grid-cols-1 gap-2 mt-2">
                                    <div v-for="(file, idx) in parseFiles(editingLead.files)" :key="idx" class="flex items-center justify-between p-3 bg-white/5 border border-white/5 rounded-2xl group hover:border-indigo-500/30 transition-all">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <span class="grayscale group-hover:grayscale-0 transition-opacity">📄</span>
                                            <div class="flex flex-col truncate">
                                                <span class="text-xs font-bold text-white truncate">{{ file.name }}</span>
                                                <span class="text-[9px] text-slate-500">{{ formatSize(file.size) }}</span>
                                            </div>
                                        </div>
                                        <a :href="route('admin.leads.download', { lead: editingLead.id, filename: file.name })" class="p-2 hover:bg-white/10 rounded-xl transition-colors">📥</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Action) -->
                    <div class="flex-1 p-10 flex flex-col h-full bg-[#1E293B]">
                        <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-6">Обработка</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Статус работы</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button 
                                            v-for="(st, key) in statuses" 
                                            :key="key"
                                            @click="form.status = key"
                                            class="py-4 px-3 rounded-2xl border text-[10px] font-black uppercase tracking-wider transition-all"
                                            :class="form.status === key ? 'bg-indigo-500 border-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-white/5 border-white/5 text-slate-500 hover:border-white/10'"
                                        >
                                            {{ st.label }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Ответственный</label>
                                    <select v-model="form.manager_id" class="w-full bg-white/5 border border-white/5 rounded-2xl text-white py-4 px-5 focus:ring-2 focus:ring-indigo-500 outline-none font-bold">
                                        <option value="" class="bg-[#1E293B]">Не назначен</option>
                                        <option v-for="m in managers" :key="m.id" :value="m.id" class="bg-[#1E293B]">{{ m.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Заметки</label>
                                        <span class="text-[9px] text-slate-600 bg-white/5 px-3 py-1 rounded-full uppercase font-black">Private</span>
                                    </div>
                                    <textarea 
                                        v-model="form.manager_notes" 
                                        rows="6" 
                                        placeholder="О чем договорились с клиентом?"
                                        class="w-full bg-white/5 border border-white/5 rounded-[2rem] text-white py-5 px-6 focus:ring-2 focus:ring-indigo-500 outline-none placeholder:text-slate-700 transition-all font-medium resize-none"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-8 border-t border-white/5 flex gap-4">
                            <button @click="deleteLead(editingLead.id)" class="py-4 px-4 text-sm font-black text-rose-500 hover:text-white bg-rose-500/10 hover:bg-rose-500 rounded-2xl transition-all uppercase tracking-widest border border-rose-500/20" title="Удалить заявку">
                                🗑️
                            </button>
                            <button @click="editingLead = null" class="flex-1 py-4 px-3 md:px-5 text-xs md:text-sm font-black text-slate-500 hover:text-white bg-white/5 hover:bg-white/10 rounded-2xl transition-all uppercase tracking-widest border border-white/5">Отмена</button>
                            <button 
                                @click="saveEdit" 
                                :disabled="form.processing"
                                class="flex-[1.5] py-4 px-3 md:px-5 text-xs md:text-sm font-black text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl shadow-indigo-500/20 hover:scale-[1.02] transition-all uppercase tracking-widest disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Сохранение...' : 'Обновить статус' }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </transition>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    leads: Object,
    filters: Object,
    userRole: [String, Number],
    managers: Array
});

const statuses = {
    new: { label: 'Новая', class: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' },
    pending_payment: { label: 'Ожидает оплаты', class: 'bg-slate-500/10 text-slate-400 border-slate-500/30' },
    awaiting_confirmation: { label: 'Проверка оплаты', class: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
    paid: { label: 'Оплачено', class: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    in_progress: { label: 'В работе', class: 'bg-blue-500/10 text-blue-400 border-blue-500/30' },
    rejected: { label: 'Отказ', class: 'bg-rose-500/10 text-rose-400 border-rose-500/30' },
    completed: { label: 'Завершена', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' },
};

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

let debounceTimer;
const updateFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('admin.leads.index'), {
            search: search.value,
            status: statusFilter.value
        }, { preserveState: true, replace: true });
    }, 400);
};

watch([search, statusFilter], updateFilters);

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
    form.patch(route('admin.leads.update', editingLead.value.id), {
        onSuccess: () => {
            editingLead.value = null;
        }
    });
};

const clearAllLeads = () => {
    if (confirm('Удалить ВСЕ заявки безвозвратно?')) {
        router.delete(route('admin.leads.destroy-all'));
    }
};

const deleteLead = (id) => {
    if (confirm('Вы уверены, что хотите удалить эту заявку? Это действие необратимо.')) {
        router.delete(route('admin.leads.destroy', id), {
            onSuccess: () => {
                editingLead.value = null;
            }
        });
    }
};

const parseFiles = (files) => {
    if (!files) return [];
    try {
        return typeof files === 'string' ? JSON.parse(files) : files;
    } catch (e) { return []; }
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.8s ease-out; }
.animate-slide-up { animation: slideUp 0.6s ease-out; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.modal-enter-active, .modal-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95) translateY(10px); }

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>

