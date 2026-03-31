<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    payments: Object,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isKasper = computed(() => Number(user.value?.role) === 3);

const currentFilter = ref(props.filters?.status || 'all');

const showSettingsModal = ref(false);
const settingsForm = ref({
    card_number: '',
    phone_number: '',
    recipient_name: '',
});

const openSettings = async () => {
    try {
        const response = await fetch('/api/public/payment-settings');
        if (response.ok) {
            settingsForm.value = await response.json();
            showSettingsModal.value = true;
        }
    } catch(e) {}
};

const saveSettings = () => {
    router.post('/admin/payments/settings', settingsForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsModal.value = false;
            // Optionally, we could show a global alert here using a store, but Inertia's back() will trigger the global flash message.
        }
    });
};

const handleFilter = (status) => {
    currentFilter.value = status;
    router.get('/admin/payments', { status }, { preserveState: true, replace: true });
};

const updateStatus = (payment, status) => {
    if (!confirm(`Вы уверены, что хотите перевести оплату #${payment.id} в статус "${status}"?`)) return;
    
    router.patch(`/admin/payments/${payment.id}/status`, { status }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const getStatusLuxClass = (status) => {
    switch(status) {
        case 'pending': return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
        case 'approved': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        case 'rejected': return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
        default: return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending': return 'Ожидает';
        case 'approved': return 'Одобрено';
        case 'rejected': return 'Отклонено';
        default: return status;
    }
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Оплаты | Admin" />
    <AdminLayout>
        <div class="max-w-7xl mx-auto space-y-8 pb-20">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 glass-panel-lux p-6 rounded-3xl">
                <div>
                    <h1 class="text-3xl font-black text-white flex items-center gap-4">
                        <span class="p-3 bg-indigo-500/20 rounded-2xl text-indigo-400 border border-indigo-500/30 shadow-[0_0_20px_rgba(99,102,241,0.2)]">💳</span>
                        Оплаты
                    </h1>
                    <p class="text-slate-400 mt-2 font-medium">Управление финансовыми поступлениями и подтверждение скриншотов</p>
                </div>
                <div v-if="isKasper" class="flex gap-3">
                    <button @click="openSettings" class="px-5 py-3 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500 hover:text-white transition-all font-bold shadow-[0_0_15px_rgba(168,85,247,0.2)]">
                        ⚙️ Реквизиты
                    </button>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="glass-panel-lux rounded-3xl overflow-hidden shadow-2xl">
                <!-- Toolbar -->
                <div class="p-5 border-b border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-900/50">
                    <div class="flex flex-wrap gap-2">
                        <button v-for="status in ['all', 'pending', 'approved', 'rejected']" :key="status"
                                @click="handleFilter(status)"
                                class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 backdrop-blur-md"
                                :class="currentFilter === status 
                                    ? 'bg-indigo-500 text-white shadow-[0_0_15px_rgba(99,102,241,0.4)] border border-indigo-400' 
                                    : 'bg-white/5 text-slate-400 border border-white/10 hover:bg-white/10 hover:text-white'">
                            {{ 
                                status === 'all' ? 'Все' : 
                                status === 'pending' ? 'Ожидают ✅' : 
                                status === 'approved' ? 'Подтвержденные' : 'Отклоненные' 
                            }}
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/80">
                                <th class="p-5 whitespace-nowrap">ID / Дата</th>
                                <th class="p-5">Заявка / Клиент</th>
                                <th class="p-5">Метод</th>
                                <th class="p-5">Сумма</th>
                                <th class="p-5">Скриншот</th>
                                <th class="p-5">Статус</th>
                                <th class="p-5 text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/50">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-800/30 transition-colors group">
                                <td class="p-5 whitespace-nowrap">
                                    <div class="font-bold text-white">#{{ payment.id }}</div>
                                    <div class="text-xs text-slate-500 font-medium">{{ new Date(payment.created_at).toLocaleString('ru') }}</div>
                                </td>
                                <td class="p-5">
                                    <template v-if="payment.lead">
                                        <div class="font-bold text-indigo-300">Заявка #{{ payment.lead.id }}</div>
                                        <div class="text-xs text-slate-400">{{ payment.lead.client_name }}</div>
                                    </template>
                                    <div v-else class="text-slate-500 italic">Заявка удалена</div>
                                </td>
                                <td class="p-5">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 font-medium text-xs">
                                        <span class="text-lg">{{ payment.method === 'card' ? '💳' : '📱' }}</span>
                                        <span class="text-slate-300 uppercase">{{ payment.method }}</span>
                                    </div>
                                </td>
                                <td class="p-5 font-black text-white whitespace-nowrap">
                                    {{ formatAmount(payment.amount) }}
                                </td>
                                <td class="p-5">
                                    <a :href="'/storage/' + payment.screenshot_path" target="_blank" class="inline-block relative rounded-lg overflow-hidden border border-slate-700/50 shadow-lg group-hover:border-indigo-500/50 transition-colors">
                                        <img :src="'/storage/' + payment.screenshot_path" alt="Скриншот" class="h-16 w-16 object-cover bg-slate-800">
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                            <span class="text-white text-xl">🔍</span>
                                        </div>
                                    </a>
                                </td>
                                <td class="p-5">
                                    <span class="px-3 py-1.5 rounded-xl font-bold text-xs uppercase tracking-wider" :class="getStatusLuxClass(payment.status)">
                                        {{ getStatusLabel(payment.status) }}
                                    </span>
                                </td>
                                <td class="p-5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2 opacity-100">
                                        <template v-if="payment.status === 'pending'">
                                            <button @click="updateStatus(payment, 'approved')" class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white hover:shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all font-bold text-sm" title="Одобрить">
                                                ✅ Одобрить
                                            </button>
                                            <button @click="updateStatus(payment, 'rejected')" class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white hover:shadow-[0_0_15px_rgba(244,63,94,0.4)] transition-all font-bold text-sm" title="Отклонить">
                                                ❌ Отклонить
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="updateStatus(payment, 'pending')" class="p-2.5 rounded-xl bg-slate-700/50 text-slate-300 hover:bg-slate-600 transition-all text-xs border border-slate-600 font-bold" title="Вернуть в ожидание">
                                                🔄 В ожидание
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!payments.data.length">
                                <td colspan="7" class="p-10 text-center text-slate-500 italic font-medium">
                                    <div class="text-4xl mb-4 opacity-50">💸</div>
                                    Нет оплат в данном статусе
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="p-5 border-t border-slate-800/50 flex justify-center bg-slate-900/50">
                    <div class="flex gap-1 bg-slate-800/50 p-1.5 rounded-2xl border border-slate-700/30">
                        <component v-for="(link, i) in payments.links" :key="i"
                            :is="link.url ? 'a' : 'span'"
                            :href="link.url"
                            v-html="link.label.replace('&laquo; Previous', '←').replace('Next &raquo;', '→')"
                            class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-500 text-white shadow-[0_0_10px_rgba(99,102,241,0.5)]' : '',
                                !link.active && link.url ? 'text-slate-400 hover:text-white hover:bg-white/5' : '',
                                !link.url ? 'text-slate-600 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div v-if="showSettingsModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showSettingsModal = false"></div>
            <div class="relative bg-slate-800 rounded-3xl p-6 w-full max-w-md border border-slate-700 shadow-2xl overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-[50px]"></div>
                
                <h2 class="text-2xl font-bold text-white mb-6 relative">⚙️ Реквизиты оплаты</h2>
                
                <div class="space-y-4 relative">
                    <div>
                        <label class="block text-slate-400 text-sm font-bold mb-2 uppercase tracking-wide">💳 Номер карты (Перевод по номеру карты)</label>
                        <input type="text" v-model="settingsForm.card_number" class="w-full bg-slate-900/50 border border-slate-700 focus:border-indigo-500 focus:bg-slate-800 rounded-xl px-4 py-4 text-white text-lg font-mono outline-none transition-all placeholder:text-slate-600" placeholder="0000 0000 0000 0000">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-sm font-bold mb-2 uppercase tracking-wide">📱 ТЕЛЕФОН (СБП В СБЕРБАНК / ТИНЬКОФФ)</label>
                        <input type="text" v-model="settingsForm.phone_number" class="w-full bg-slate-900/50 border border-slate-700 focus:border-indigo-500 focus:bg-slate-800 rounded-xl px-4 py-4 text-white text-lg font-mono outline-none transition-all placeholder:text-slate-600" placeholder="+7 (000) 000-00-00">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-sm font-bold mb-2 uppercase tracking-wide">👤 ИМЯ ПОЛУЧАТЕЛЯ (ДЛЯ СВЕРКИ)</label>
                        <input type="text" v-model="settingsForm.recipient_name" class="w-full bg-slate-900/50 border border-slate-700 focus:border-indigo-500 focus:bg-slate-800 rounded-xl px-4 py-4 text-white text-lg outline-none transition-all placeholder:text-slate-600" placeholder="Иван И. (Сбербанк/Тинькофф)">
                    </div>
                </div>

                <div class="mt-8 flex gap-3 justify-end relative">
                    <button @click="showSettingsModal = false" class="px-5 py-3 rounded-xl bg-slate-800 text-slate-300 font-medium hover:bg-slate-700 transition-colors border border-slate-700">
                        Отмена
                    </button>
                    <button @click="saveSettings" class="px-5 py-3 rounded-xl bg-indigo-500 text-white font-bold hover:bg-indigo-600 hover:shadow-[0_0_20px_rgba(99,102,241,0.4)] transition-all shadow-lg shadow-indigo-500/20">
                        💾 Сохранить реквизиты
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.glass-panel-lux {
    background: rgba(30, 41, 59, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
</style>
