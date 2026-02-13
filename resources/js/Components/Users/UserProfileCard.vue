<template>
    <div class="card border-light border-2">
        <div class="card-body d-flex justify-content-center flex-column align-items-center">

            <!-- Имя -->
            <h5
                @click="minimize=!minimize"
                class="card-title mb-0 text-decoration-underline link-underline-primary">{{ user.name }}</h5>

            <p class="mb-1 fst-italic small text-primary fw-bold">
                {{ roleLabel(user.role) }}
            </p>

            <template v-if="!minimize">

                <p class="text-muted mb-0">{{ user.fio_from_telegram || '—' }}</p>

<!--                &lt;!&ndash; Email &ndash;&gt;
                <p class="mb-1">
                    {{ user.email }}
                </p>-->

                <!-- Telegram -->
                <p class="mb-1" v-if="user.telegram_chat_id">
                    {{ user.telegram_chat_id }}
                </p>


                <template v-if="user.role>=3">
                    <!-- Процент -->
                    <p class="mb-1">
                        Админский {{ user.percent }} %
                    </p>

                    <div
                        class="form-check form-switch ">
                        <input
                            @change="changeAdminWorkStatus(user)"
                            class="form-check-input"
                            type="checkbox"
                            v-model="user.is_work"
                            :id="`is-work-${user.id}`"
                        />
                        <label class="form-check-label" :for="`is-work-${user.id}`">
                            {{ user.is_work ? 'за работай' : 'не работаю' }}
                        </label>
                    </div>
                </template>



<!--                &lt;!&ndash; Кнопка редактирования &ndash;&gt;
                <div class="mt-3">
                    <a :href="`/users/${user.id}/edit`" class="btn btn-outline-primary btn-sm">
                        Редактировать
                    </a>
                </div>-->
            </template>

        </div>
    </div>
</template>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    name: 'UserProfileCard',
    data() {
        return {
            userStore: useUsersStore(),
            minimize: true
        }
    },
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    mounted() {
        console.log("user in usercard", this.user)
    },
    methods: {
        async changeAdminWorkStatus(user) {
            await this.userStore.updateWorkStatus(user.id, user.is_work)
        },
        roleLabel(role) {
            const roles = {
                0: 'Пользователь',
                1: 'Младший администратор',
                2: 'Поставщик',
                3: 'Администратор',
                4: 'Суперадмин'
            }
            return roles[role] || 'Неизвестно'
        }
    }
}
</script>
