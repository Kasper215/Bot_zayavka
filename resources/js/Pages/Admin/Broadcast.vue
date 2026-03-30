<template>
    <Head title="Рассылка PWA | BioBook Lux" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in">
            <div>
                <h1 class="text-4xl font-black text-white tracking-tight italic">Broadcast</h1>
                <p class="text-slate-400 mt-2 font-medium">Мгновенные PWA Push-уведомления для клиентов</p>
            </div>
            
            <div class="flex items-center gap-4 bg-[#1E293B]/40 backdrop-blur-xl border border-white/5 p-4 rounded-3xl">
                <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-2xl flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-black text-white leading-none">{{ pushSubscribersCount }}</div>
                    <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Подписчиков PWA</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-10 items-start animate-slide-up">
            
            <!-- Send Form -->
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500/10 to-purple-600/10 rounded-[3rem] blur-2xl opacity-50 group-hover:opacity-100 transition duration-1000"></div>
                
                <div class="relative bg-[#1E293B]/60 backdrop-blur-2xl border border-white/10 rounded-[3rem] p-8 lg:p-12 shadow-2xl overflow-hidden">
                    <h2 class="text-xl font-black text-white mb-8 border-l-4 border-indigo-500 pl-4 uppercase tracking-wider">Создание потока</h2>

                    <form @submit.prevent="submit" class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-2">Заголовок</label>
                            <input 
                                v-model="form.title"
                                type="text"
                                placeholder="Напр: 🚀 Скидка 20% только сегодня!"
                                class="w-full bg-white/5 border border-white/5 rounded-2xl p-4 text-white placeholder:text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold"
                                required
                            >
                            <div v-if="form.errors.title" class="text-rose-500 text-xs mt-2 font-bold">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-2">Текст уведомления</label>
                            <textarea
                                v-model="form.message"
                                rows="4"
                                placeholder="Опишите ваше предложение максимально кратко и ёмко..."
                                class="w-full bg-white/5 border border-white/5 rounded-[2rem] p-6 text-white placeholder:text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium resize-none leading-relaxed"
                                required
                            ></textarea>
                            <div v-if="form.errors.message" class="text-rose-500 text-xs mt-2 font-bold">{{ form.errors.message }}</div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-2">URL при нажатии (опционально)</label>
                            <input 
                                v-model="form.url"
                                type="url"
                                placeholder="https://biobook.com/promo"
                                class="w-full bg-white/5 border border-white/5 rounded-2xl p-4 text-white placeholder:text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-bold"
                            >
                        </div>

                        <div class="pt-6 border-t border-white/5">
                            <button
                                type="submit"
                                :disabled="form.processing || !form.message || !form.title"
                                class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:scale-[1.02] active:scale-95 text-white py-5 rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-500/20 transition-all flex items-center justify-center gap-4 disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="animate-spin text-xl">🌀</span>
                                {{ form.processing ? 'Запуск...' : 'Запустить поток' }}
                                <svg v-if="!form.processing" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Column -->
            <div class="sticky top-24 hidden lg:block animate-fade-in delay-200">
                <div class="mb-6 ml-4 text-[11px] font-black text-slate-500 uppercase tracking-[0.3em] flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-slate-800"></span>
                    Live Preview
                </div>

                <!-- Phone Frame mockup (Simplified) -->
                <div class="relative mx-auto w-full max-w-[340px] h-[600px] bg-[#0F172A] rounded-[3.5rem] border-[8px] border-[#1E293B] shadow-[0_0_100px_rgba(30,41,59,0.5)] overflow-hidden">
                    <div class="absolute top-0 inset-x-0 h-10 flex items-center justify-center">
                        <div class="w-24 h-4 bg-[#1E293B] rounded-full"></div>
                    </div>
                    
                    <!-- Notification Banner (The Highlight) -->
                    <div class="mt-20 px-4">
                        <transition name="pop">
                            <div v-if="form.title || form.message" class="bg-white/95 backdrop-blur-xl rounded-[1.5rem] p-4 shadow-2xl flex items-start gap-3">
                                <div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center shadow-lg transform rotate-6">
                                    <span class="text-white text-xl">B</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <h4 class="text-slate-900 font-black text-xs truncate uppercase tracking-tighter">BioBook App</h4>
                                        <span class="text-[9px] text-slate-400 font-bold whitespace-nowrap">now</span>
                                    </div>
                                    <h5 class="text-slate-900 font-bold text-[13px] leading-tight mb-0.5 break-words line-clamp-1">{{ form.title || 'Notification Title' }}</h5>
                                    <p class="text-slate-600 text-[11px] leading-snug line-clamp-2">{{ form.message || 'Notification description will appear here as soon as you start typing...' }}</p>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- Lockscreen Time Mockup -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center -z-10 opacity-30 pointer-events-none">
                        <div class="text-6xl font-thin text-white mb-2">12:44</div>
                        <div class="text-xs text-white uppercase tracking-[0.3em]">Monday, May 24</div>
                    </div>
                    
                    <div class="absolute bottom-8 inset-x-0 flex items-center justify-center gap-10 opacity-40">
                        <div class="w-10 h-10 bg-white/10 rounded-full"></div>
                        <div class="w-10 h-10 bg-white/10 rounded-full"></div>
                    </div>
                </div>

                <div class="mt-8 bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-[2rem] max-w-[340px] mx-auto">
                    <div class="flex items-start gap-4">
                        <span class="text-2xl">🔒</span>
                        <p class="text-[10px] text-emerald-400 font-bold leading-relaxed uppercase tracking-wider">Ваши данные передаются только по зашифрованному каналу PWA Push API. Мы не используем сторонние сервисы для доставки.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Success Flash -->
        <transition name="flash">
            <div v-if="showFlash" class="fixed bottom-10 right-10 z-[100] bg-indigo-500 text-white px-8 py-5 rounded-[2rem] shadow-[0_20px_60px_-15px_rgba(99,102,241,0.5)] font-black uppercase tracking-widest text-xs flex items-center gap-4 border border-white/20">
                <div class="w-8 h-8 bg-white/20 rounded-xl flex items-center justify-center">🚀</div>
                {{ flashMsg }}
            </div>
        </transition>

    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    pushSubscribersCount: Number,
});

const form = useForm({
    title: '',
    message: '',
    url: '',
});

const showFlash = ref(false);
const flashMsg = ref('');

const submit = () => {
    form.post(route('admin.broadcast.send'), {
        preserveScroll: true,
        onSuccess: () => {
            flashMsg.value = usePage().props.flash?.success || 'Рассылка запущена!';
            showFlash.value = true;
            form.reset();
            setTimeout(() => showFlash.value = false, 6000);
        },
    });
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.8s ease-out; }
.animate-slide-up { animation: slideUp 0.6s ease-out; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.pop-enter-active { animation: popIn 0.5s cubic-bezier(0.68, -0.6, 0.32, 1.6); }
@keyframes popIn { from { transform: scale(0.8) translateY(-20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

.flash-enter-active, .flash-leave-active { transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1); }
.flash-enter-from, .flash-leave-to { transform: translateX(100px); opacity: 0; }
</style>
