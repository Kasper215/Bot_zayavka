<template>

    <form @submit.prevent="submitForm">

        <!-- Имя -->
        <div class="form-floating mb-2">
            <input v-model="form.name" type="text" class="form-control" id="name" placeholder="Имя" required>
            <label for="name">Имя</label>
        </div>

        <!-- Email -->
        <div class="form-floating mb-2">
            <input v-model="form.email" type="email" class="form-control" id="email" placeholder="Email" required>
            <label for="email">Email</label>
        </div>

        <!-- Telegram Chat ID -->
        <div class="form-floating mb-2">
            <input v-model="form.telegram_chat_id" type="text" class="form-control" id="telegram_chat_id"
                   placeholder="Telegram Chat ID">
            <label for="telegram_chat_id">Telegram Chat ID</label>
        </div>

        <!-- Роль -->
        <div class="form-floating mb-2">
            <select v-model="form.role" class="form-select" id="role" required>
                <option :value="0">Пользователь</option>
                <option :value="1">Младший администратор</option>
                <option :value="2">Поставщик</option>
                <option :value="3">Администратор</option>
                <option :value="4">Суперадмин</option>
            </select>
            <label for="role">Роль</label>
        </div>

        <!-- Процент -->
        <div class="form-floating mb-2">
            <input v-model="form.percent" type="number" step="0.01" class="form-control" id="percent"
                   placeholder="Процент">
            <label for="percent">Процент</label>
        </div>

        <!--            &lt;!&ndash; Пароль &ndash;&gt;
                    <div class="form-floating mb-2">
                        <input v-model="form.password" type="password" class="form-control" id="password" placeholder="Пароль" required>
                        <label for="password">Пароль</label>
                    </div>-->

        <!-- Кнопка -->
        <button type="submit" class="btn btn-primary w-100 p-3">
            {{ isEdit ? 'Сохранить изменения' : 'Создать пользователя' }}
        </button>
    </form>

</template>

<script>
import axios from 'axios'
import {useUsersStore} from "@/stores/users";

export default {
    name: 'UserForm',
    props: {
        initialData: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            userStore: useUsersStore(),
            form: {
                name: '',
                email: '',
                telegram_chat_id: '',
                role: 0,
                percent: 0,
                password: ''
            },
            isEdit: false
        }
    },
    created() {
        if (this.initialData) {
            this.form = {...this.initialData}
            this.isEdit = true
        }
    },
    methods: {
        async submitForm() {
            await this.userStore.update(this.form.id, this.form)
            this.$emit("saved")
        }
    }
}
</script>
