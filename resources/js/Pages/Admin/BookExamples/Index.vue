<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    examples: Array
});

const showModal = ref(false);
const editingExample = ref(null);

const form = useForm({
    title: '',
    description: '',
    tag: '',
    cover: null,
    pdf: null,
    order_index: 0,
    is_visible: true,
});

const openCreateModal = () => {
    editingExample.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (example) => {
    editingExample.value = example;
    form.title = example.title;
    form.description = example.description;
    form.tag = example.tag;
    form.order_index = example.order_index;
    form.is_visible = !!example.is_visible;
    form.cover = null;
    form.pdf = null;
    showModal.value = true;
};

const submit = () => {
    if (editingExample.value) {
        // Загрузка файлов через POST с переопределением метода (для Laravel)
        form.post(route('admin.book-examples.update', editingExample.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.book-examples.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteExample = (example) => {
    if (confirm('Вы уверены, что хотите удалить этот пример?')) {
        router.delete(route('admin.book-examples.destroy', example.id));
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};
</script>

<template>
    <Head title="Управление примерами книг" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Примеры в галерее</h2>
                        <button 
                            @click="openCreateModal"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl transition-all"
                        >
                            + Добавить пример
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="example in examples" :key="example.id" class="border rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-48 bg-gray-100 relative">
                                <img v-if="example.cover_path" :src="example.cover_path" class="w-full h-full object-cover">
                                <div class="absolute top-2 right-2 flex gap-2">
                                    <span :class="example.is_visible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded-md text-xs font-bold uppercase">
                                        {{ example.is_visible ? 'Виден' : 'Скрыт' }}
                                    </span>
                                    <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-md text-xs font-bold uppercase">
                                        Порядок: {{ example.order_index }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-1">{{ example.title }}</h3>
                                <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ example.description }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">{{ example.tag }}</span>
                                    <div class="flex gap-2">
                                        <button @click="openEditModal(example)" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold">Изменить</button>
                                        <button @click="deleteExample(example)" class="text-red-500 hover:text-red-700 text-sm font-bold">Удалить</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="examples.length === 0" class="text-center py-12 text-gray-500">
                        Пока нет ни одного примера. Добавьте первый!
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-6 border-b">
                    <h3 class="text-xl font-bold">{{ editingExample ? 'Редактировать' : 'Добавить' }} пример</h3>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Название книги</label>
                        <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Описание</label>
                        <textarea v-model="form.description" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500" rows="3"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Тэг (тип книги)</label>
                            <input v-model="form.tag" type="text" placeholder="Напр: Сказка" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Порядковый номер</label>
                            <input v-model="form.order_index" type="number" class="w-full border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4 py-2 border-y border-gray-50">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Обложка (Image)</label>
                            <input type="file" @input="form.cover = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="text-[10px] text-gray-400 mt-1" v-if="editingExample">Оставьте пустым, чтобы не менять</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Файл книги (PDF)</label>
                            <input type="file" @input="form.pdf = $event.target.files[0]" accept=".pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="text-[10px] text-gray-400 mt-1" v-if="editingExample">Оставьте пустым, чтобы не менять</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_visible" id="is_visible" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_visible" class="text-sm font-bold text-gray-700">Отображать в галерее</label>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="closeModal" class="px-6 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold transition-colors">Отмена</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-100 transition-all disabled:opacity-50">
                            {{ form.processing ? 'Сохранение...' : (editingExample ? 'Обновить' : 'Создать') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
