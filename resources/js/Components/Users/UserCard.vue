<template>
    <ul class="list-group list-group-flush">
        <li class="list-group-item">
            <strong>Имя в системе:</strong> {{ user.name }}
        </li>
        <li class="list-group-item">
            <strong>Имя из Telegram:</strong> {{ user.fio_from_telegram || 'не указано' }}
        </li>
        <li class="list-group-item">
            <strong>Email:</strong> {{ user.email }}
        </li>
        <li class="list-group-item">
            <strong>Telegram Chat ID:</strong> {{ user.telegram_chat_id || 'не указан' }}
        </li>
        <li class="list-group-item">
            <strong>Роль:</strong> {{ roleName(user.role) }}
        </li>
        <li class="list-group-item">
            <strong>Процент:</strong> {{ user.percent }}%
        </li>
    </ul>

    <button class="btn btn-primary w-100 p-3" @click="$emit('edit', user)">Редактировать</button>

</template>

<script>
export default {
    name: 'UserCard',
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    methods: {
        roleName(role) {
            switch (role) {
                case 0: return 'Пользователь'
                case 1: return 'Младший администратор'
                case 2: return 'Поставщик'
                case 3: return 'Администратор'
                case 4: return 'Суперадмин'
                default: return 'Неизвестно'
            }
        }
    }
}
</script>
