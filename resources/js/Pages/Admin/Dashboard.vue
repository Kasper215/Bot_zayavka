<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leadsByDay: Array,
    leadsByService: Array,
    stats: Object
});

// Форматирование даты для графика
const chartDataDays = computed(() => {
    return props.leadsByDay.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
    });
});

const chartDataCounts = computed(() => {
    return props.leadsByDay.map(d => d.count);
});

// Максимальное значение для масштабирования графика
const maxCount = computed(() => Math.max(...chartDataCounts.value, 5));
</script>

<template>
    <Head title="Статистика" />

    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900">Дашборд</h1>
            <p class="text-sm text-slate-500 mt-1">Обзор ключевых показателей эффективности бота</p>
        </template>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-slate-500 text-sm font-medium mb-2 uppercase tracking-wider">Всего заявок</div>
                <div class="flex items-end gap-2">
                    <div class="text-3xl font-bold text-slate-900">{{ stats.total_leads }}</div>
                    <div class="text-indigo-600 text-sm font-semibold mb-1">за всё время</div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-slate-500 text-sm font-medium mb-2 uppercase tracking-wider">Новые</div>
                <div class="flex items-end gap-2">
                    <div class="text-3xl font-bold text-amber-600">{{ stats.new_leads }}</div>
                    <div class="text-slate-400 text-sm mb-1">ждут обработки</div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-slate-500 text-sm font-medium mb-2 uppercase tracking-wider">Пользователи</div>
                <div class="flex items-end gap-2">
                    <div class="text-3xl font-bold text-slate-900">{{ stats.total_users }}</div>
                    <div class="text-slate-400 text-sm mb-1">в базе бота</div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-slate-500 text-sm font-medium mb-2 uppercase tracking-wider">Конверсия</div>
                <div class="flex items-end gap-2">
                    <div class="text-3xl font-bold text-indigo-600">{{ stats.conversion_rate }}%</div>
                    <div class="text-slate-400 text-sm mb-1">из старта в лида</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Простой график (имитация через SVG) -->
            <div class="lg:col-span-2 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-6">Динамика заявок (30 дней)</h3>
                <div v-if="leadsByDay.length > 0" class="h-64 flex items-end justify-between gap-1 overflow-x-auto">
                    <div v-for="(count, index) in chartDataCounts" :key="index" class="flex-1 flex flex-col items-center group cursor-pointer">
                        <div class="w-full bg-indigo-100 group-hover:bg-indigo-200 rounded-t-lg transition-all duration-300 relative min-h-[4px]" 
                             :style="{ height: (count / maxCount * 100) + '%' }">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ count }}
                            </div>
                        </div>
                        <div class="mt-2 text-[10px] text-slate-400 rotate-45 origin-left whitespace-nowrap">
                            {{ chartDataDays[index] }}
                        </div>
                    </div>
                </div>
                <div v-else class="h-64 flex items-center justify-center text-slate-400 italic">
                    Недостаточно данных для графика
                </div>
            </div>

            <!-- Распределение по жанрам -->
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-6">Популярные услуги</h3>
                <div class="space-y-4">
                    <div v-for="item in leadsByService" :key="item.service_type" class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 font-medium capitalize">{{ item.service_type || 'Неизвестно' }}</span>
                            <span class="text-slate-900 font-bold">{{ item.count }}</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full" :style="{ width: (item.count / stats.total_leads * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
