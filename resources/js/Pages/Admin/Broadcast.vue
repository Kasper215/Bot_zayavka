<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    userCount: Number,
});

const form = useForm({
    message: '',
    image: null,
});

const showSuccess = ref(false);
const broadcastMessage = ref('');
const imagePreview = ref(null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
};

const submit = () => {
    form.post(route('admin.broadcast.send'), {
        onSuccess: (page) => {
            form.reset();
            imagePreview.value = null;
            broadcastMessage.value = page.props.flash?.success || 'Рассылка успешно завершена!';
            showSuccess.value = true;
            setTimeout(() => showSuccess.value = false, 5000);
        },
    });
};
</script>

<template>
    <Head title="Рассылка сообщений" />

    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Рассылка</h1>
                <p class="text-sm text-slate-500 mt-1">Отправка мгновенных сообщений всем пользователям бота</p>
            </div>
        </template>

        <div class="max-w-4xl">
            <!-- Статистика -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-900">{{ userCount }}</div>
                        <div class="text-sm text-slate-500">Активных пользователей</div>
                    </div>
                </div>

                <div v-if="showSuccess" class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl flex items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="text-emerald-800 font-medium">{{ broadcastMessage }}</div>
                </div>
            </div>

            <!-- Форма рассылки -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h2 class="text-lg font-bold text-slate-900">Новое сообщение</h2>
                </div>
                
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Текст сообщения</label>
                        <textarea
                            v-model="form.message"
                            rows="8"
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 p-4 text-slate-700 placeholder:text-slate-400"
                            placeholder="Введите текст акции, новости или важного уведомления..."
                            required
                        ></textarea>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                            <span>Поддерживается HTML разметка (&lt;b&gt;, &lt;i&gt;, &lt;a&gt;)</span>
                            <span>Символов: {{ form.message.length }}</span>
                        </div>
                        <div v-if="form.errors.message" class="text-red-500 text-sm mt-1">{{ form.errors.message }}</div>
                    </div>

                    <!-- Изображение -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Изображение (опционально)</label>
                        
                        <div v-if="imagePreview" class="relative inline-block">
                            <img :src="imagePreview" class="w-64 h-auto rounded-2xl border-2 border-slate-100 shadow-md" />
                            <button 
                                @click="removeImage" 
                                type="button"
                                class="absolute -top-2 -right-2 bg-rose-500 text-white p-1.5 rounded-full hover:bg-rose-600 shadow-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-else class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mb-2 text-sm text-slate-500"><span class="font-bold">Нажмите, чтобы загрузить</span> или перетащите</p>
                                    <p class="text-xs text-slate-400">PNG, JPG или JPEG (МАКС. 10МБ)</p>
                                </div>
                                <input @change="onFileChange" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-slate-600">
                                <p class="font-semibold text-slate-700">Внимание!</p>
                                <p class="mt-0.5">Сообщение будет отправлено всем <b>{{ userCount }}</b> пользователям. Это действие нельзя отменить. Рассылка займет некоторое время.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.message"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-100 transition-all duration-200 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Отправка...' : 'Запустить рассылку' }}</span>
                            <svg v-if="!form.processing" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
