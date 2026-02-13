<template>


        <div class="form-floating my-2 w-100">

            <select
                id="roleSelect"
                class="form-select"
                v-model="selectedRole"
                @change="changeRole"
            >
                <option :value="0">Пользователь</option>
                <option :value="1">Волонтер</option>
                <option :value="2">Должностное лицо</option>
                <option :value="3">Администратор</option>
                <option :value="4">Суперадмин</option>
            </select>

            <label for="roleSelect" class="form-label">Сменить роль</label>
        </div>
        <p class="mt-2">
            Текущая роль: <strong>{{ roleLabel }}</strong>
        </p>
        <p class="mt-2" v-if="roleLabel!==baseRoleLabel">
            Базовая роль: <strong>{{ baseRoleLabel }}</strong>
        </p>


</template>

<script>

import {useUsersStore} from "@/stores/users";

export default {
    name: 'RoleSwitcher',
    data() {
        return {
            userStore: useUsersStore(),
            selectedRole: useUsersStore().self.role || 0,
            roles: {
                0: 'Пользователь',
                1: 'Младший администратор',
                2: 'Поставщик',
                3: 'Администратор',
                4: 'Суперадмин'
            },
        }
    },
    computed: {
        baseRoleLabel() {
            return this.roles[this.userStore.self.base_role ?? 0]
        },
        roleLabel() {
            return this.roles[this.userStore.self.role ?? 0]
        }
    },
    methods: {
        changeRole() {
            this.userStore.setRole(this.selectedRole)
            this.$emit('role-changed', this.selectedRole)
        }
    }
}
</script>


