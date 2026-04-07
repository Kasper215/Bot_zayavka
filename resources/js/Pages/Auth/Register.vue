<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Регистрация | BioBook" />

    <div class="auth-page min-h-screen flex flex-col justify-center items-center px-4 py-12">
        <!-- Background elements -->
        <div class="background-glows">
            <div class="glow glow-1"></div>
            <div class="glow glow-2"></div>
        </div>

        <div class="auth-card glass-panel w-full sm:max-w-md relative z-10 overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="mb-8 text-center">
                    <Link href="/" class="inline-flex items-center gap-3 mb-6 group">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-indigo-500/20 group-hover:scale-110 transition-transform">B</div>
                        <span class="text-3xl font-black tracking-tighter text-white">BioBook</span>
                    </Link>
                    <h2 class="text-2xl font-bold text-white mb-2">Создание аккаунта</h2>
                    <p class="text-indigo-200/60 text-sm">Присоединяйтесь к сообществу авторов</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="input-field">
                        <label class="block text-xs font-bold text-indigo-300 uppercase tracking-widest mb-2 ml-1">Имя</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="modern-input"
                            placeholder="Ваше имя"
                            required
                            autofocus
                        />
                        <div v-if="form.errors.name" class="error-msg">{{ form.errors.name }}</div>
                    </div>

                    <div class="input-field">
                        <label class="block text-xs font-bold text-indigo-300 uppercase tracking-widest mb-2 ml-1">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="modern-input"
                            placeholder="email@example.com"
                            required
                        />
                        <div v-if="form.errors.email" class="error-msg">{{ form.errors.email }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="input-field">
                            <label class="block text-xs font-bold text-indigo-300 uppercase tracking-widest mb-2 ml-1">Пароль</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="modern-input"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                        <div class="input-field">
                            <label class="block text-xs font-bold text-indigo-300 uppercase tracking-widest mb-2 ml-1">Повтор</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                class="modern-input"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                    </div>
                    <div v-if="form.errors.password" class="error-msg">{{ form.errors.password }}</div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="premium-btn w-full py-4 mt-4"
                    >
                        <span v-if="form.processing">ОБРАБОТКА...</span>
                        <span v-else>ЗАРЕГИСТРИРОВАТЬСЯ</span>
                    </button>
                </form>

                <div class="mt-10 pt-6 border-t border-white/5 text-center">
                    <p class="text-sm text-indigo-200/50">
                        Уже есть аккаунт?
                        <Link :href="route('login')" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors ml-1">Войти</Link>
                    </p>
                </div>
            </div>
        </div>
        
        <Link href="/" class="mt-8 text-indigo-300/40 hover:text-indigo-300 transition-colors text-sm flex items-center gap-2">
            <span>←</span> Вернуться на главную
        </Link>
    </div>
</template>

<style scoped>
.auth-page {
    background-color: #0f172a;
    color: #fff;
    font-family: 'Inter', sans-serif;
    position: relative;
    overflow: hidden;
}

.background-glows {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.glow {
    position: absolute;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.15;
}

.glow-1 {
    top: -100px;
    right: -100px;
    background: radial-gradient(circle, #6366f1, #a855f7);
}

.glow-2 {
    bottom: -100px;
    left: -100px;
    background: radial-gradient(circle, #3b82f6, #6366f1);
}

.glass-panel {
    background: rgba(30, 41, 59, 0.7);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.modern-input {
    width: 100%;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 14px 20px;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-input:focus {
    outline: none;
    border-color: #6366f1;
    background: rgba(15, 23, 42, 0.8);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.premium-btn {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    color: white;
    border: none;
    border-radius: 16px;
    font-weight: 800;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
}

.premium-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5);
    filter: brightness(1.1);
}

.premium-btn:active {
    transform: translateY(0);
}

.premium-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.error-msg {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 6px;
    font-weight: 500;
    padding-left: 4px;
}
</style>
