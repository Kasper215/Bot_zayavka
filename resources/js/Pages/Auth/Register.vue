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
    <Head title="Регистрация" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50 px-4">
        <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-2xl shadow-slate-200/50 overflow-hidden sm:rounded-2xl border border-slate-100">
            <div class="mb-8 text-center">
                <Link href="/" class="inline-flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-200">B</div>
                    <span class="text-2xl font-bold tracking-tight text-slate-900">BioBook</span>
                </Link>
                <h2 class="text-xl font-bold text-slate-800">Создание аккаунта</h2>
                <p class="text-slate-500 text-sm mt-1">Начните управлять вашим ботом уже сегодня</p>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5 pl-1">Имя</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 py-3"
                            required
                            autofocus
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5 pl-1">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 py-3"
                            required
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5 pl-1">Пароль</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 py-3"
                            required
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5 pl-1">Повторите пароль</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 py-3"
                            required
                        />
                        <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-100 transition-all duration-200 active:scale-[0.98] disabled:opacity-70 mt-6"
                    >
                        Зарегистрироваться
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-sm text-slate-600">
                    Уже есть аккаунт?
                    <Link :href="route('login')" class="text-indigo-600 font-bold hover:text-indigo-800 ml-1">Войти</Link>
                </p>
            </div>
        </div>
    </div>
</template>
