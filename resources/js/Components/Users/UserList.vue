<script setup>
import UserForm from "@/Components/Users/UserForm.vue";
import UserCard from "@/Components/Users/UserCard.vue";
import Pagination from "@/Components/Pagination.vue";
import UserFilter from "@/Components/Users/UserFilter.vue";
</script>

<template>


    <template v-if="!forSelect">
        <UserFilter v-on:apply-filters="applyFilter"></UserFilter>
    </template>

    <ul class="list-group">
        <li v-for="user in usersStore.items" :key="user.id"
            class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">
                    <span v-if="field_visible?.name||true">{{ user.name }}</span>
                    <span v-if="field_visible?.id||false">(#{{ user.id }})</span></div>
                <p class="text-muted small mb-0" v-if="field_visible?.role||true">Роль <span class="text-primary fw-bold">{{ roles.at(user.role || 0) }}</span></p>
                <p class="text-muted small mb-0" v-if="field_visible?.email||false">Почта {{ user.email }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.phone||true">Телефон {{ user.phone || '-' }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.telegram_chat_id||false">
                    ТГ id <a href="javascript:void(0)" @click.prevent="getTelegramLink(user)">{{
                        user.telegram_chat_id || 'Не указано'
                    }} </a>
                </p>
                <p class="text-muted small mb-0" v-if="field_visible?.role||false">
                    {{ roles[user.role || 0] || 'неизвестная роль' }}
                </p>
                <p class="text-muted small mb-0" v-if="field_visible?.percent||false">Процент за работу {{
                        user.percent
                    }}%</p>
                <p class="text-muted small mb-0" v-if="field_visible?.is_work||false">Статус работы
                    {{ user.is_work ? 'работает' : 'не работает' }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.email_verified_at||false">Дата верификации почты {{
                        user.email_verified_at
                    }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.blocked_at||false">Дата блокировки {{
                        user.blocked_at
                    }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.created_at||false">Дата создания пользователя
                    {{ user.created_at }}</p>
                <p class="text-muted small mb-0" v-if="field_visible?.updated_at||false">Дата последнего обновления данных
                    пользователя {{ user.updated_at }}</p>

            </div>

            <!-- Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm" type="button"
                        data-bs-toggle="dropdown">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <template v-if="forSelect">
                        <li><a class="dropdown-item" href="javascript:void(0)" @click.prevent="$emit('select', user)">Выбрать</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    </template>
                    <li><a class="dropdown-item" href="javascript:void(0)" @click.prevent="openRoleSwitcher(user)">Сменить
                        роль</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0)"
                           @click.prevent="openEdit(user)">Редактировать</a></li>

                    <li><a class="dropdown-item" href="javascript:void(0)"
                           @click.prevent="openView(user)">Просмотреть</a></li>
                    <hr class="dropdown-divider">
                    <li><a class="dropdown-item" href="javascript:void(0)" @click.prevent="getTelegramLink(user)">Получить
                        ссылку на
                        телеграм</a></li>
                    <hr class="dropdown-divider">
                    <li><a class="dropdown-item text-danger" href="javascript:void(0)"
                           @click.prevent="confirmDelete(user)">Удалить</a>
                    </li>
                    <li v-if="!user.blocked_at"><a class="dropdown-item text-danger" href="javascript:void(0)"
                                                   @click.prevent="confirmBlocked(user)">Заблокировать</a>
                    </li>
                    <li v-if="user.blocked_at"><a class="dropdown-item text-danger" href="javascript:void(0)"
                                                  @click.prevent="confirmUnBlocked(user)">Разблокировать</a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>

    <!-- Пагинация -->
    <Pagination
        :pagination="usersStore.pagination"
        @page-changed="fetchUsersByUrl"
    />
    <!-- Сообщение если список пуст -->
    <div v-if="usersStore.items.length === 0" class="alert alert-light mt-3">
        Пользователей пока нет.
    </div>

    <!-- Модалка редактирования -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <UserForm v-if="selectedUser" :initialData="selectedUser" @saved="fetchUsers"/>
                </div>
            </div>
        </div>
    </div>


    <!-- Модалка редактирования -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <UserForm v-if="selectedUser" :initialData="selectedUser" @saved="fetchUsers"/>
                </div>
            </div>
        </div>
    </div>


    <!-- Модалка редактирования -->
    <div class="modal fade" id="roleSwitcherUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование роли</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form
                        v-on:submit.prevent="changeRole"
                        v-if="selectedUser">
                        <div class="form-floating mb-2">
                            <select v-model="selectedUser.role" class="form-select" id="role" required>
                                <option :value="0">Пользователь</option>
                                <option :value="1">Младший администратор</option>
                                <option :value="2">Поставщик</option>
                                <option :value="3">Администратор</option>
                                <option :value="4">Суперадмин</option>
                            </select>
                            <label for="role">Роль</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 p-3">
                            Сохранить изменения
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- Модалка просмотра -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Информация о пользователе</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <UserCard v-if="selectedUser" :user="selectedUser"
                              @edit="openEdit"/>
                </div>
            </div>
        </div>
    </div>


</template>

<script>
import {useUsersStore} from '@/stores/users'
import {useModalStore} from '@/stores/utillites/useConfitmModalStore'

export default {
    name: 'UserList',
    props: ["forSelect"],
    data() {
        return {
            roles: [
                'Пользователь',
                'Младший администратор',
                'Поставщик',
                'Администратор',
                'Суперадмин'
            ],
            modalStore: useModalStore(),
            usersStore: useUsersStore(),
            selectedUser: null,
            field_visible: null,
        }
    },

    created() {
        this.fetchUsers()
    },

    methods: {

        async fetchUsers(page = 1) {
            await this.usersStore.fetchAllByPage(page)

        },

        async getTelegramLink(user) {
            await this.usersStore.getTelegramLink(user.id)
        },
        async fetchUsersByUrl(url) {
            await this.usersStore.fetchByUrl(url)
        },
        applyFilter(payload) {
            this.field_visible = payload.field_visible
            console.log("test", this.field_visible)
            this.usersStore.setFilters(payload.filters)
            this.usersStore.setSort(payload.sort.field, payload.sort.direction)
            this.usersStore.fetchFiltered()
        },
        openRoleSwitcher(user) {
            this.selectedUser = user
            new bootstrap.Modal(document.getElementById('roleSwitcherUserModal')).show()

        },
        openEdit(user) {
            this.selectedUser = user
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewUserModal'))
            if (viewModal)
                viewModal.hide()
            new bootstrap.Modal(document.getElementById('editUserModal')).show()

        },

        confirmDelete(user) {
            this.selectedUser = user
            this.modalStore.open(
                `Вы уверены, что хотите удалить ${this.selectedUser?.name}?`,
                () => this.usersStore.remove(this.selectedUser.id),
                () => this.modalStore.close()
            )
        },
        confirmUnBlocked(user) {
            this.selectedUser = user
            this.modalStore.open(
                `Вы уверены, что хотите разблокировать ${this.selectedUser?.name}?`,
                () => this.usersStore.unblock(this.selectedUser.id),
                () => this.modalStore.close()
            )
        },

        confirmBlocked(user) {
            this.selectedUser = user
            this.modalStore.open(
                `Вы уверены, что хотите заблокировать ${this.selectedUser?.name}?`,
                () => this.usersStore.block(this.selectedUser.id, ''),
                () => this.modalStore.close()
            )
        },


        openView(user) {
            this.selectedUser = user
            new bootstrap.Modal(document.getElementById('viewUserModal')).show()
        },

        // 🔹 Дополнительные методы для ролей и блокировки
        async changeRole() {
            await this.usersStore.updateRole(this.selectedUser.id, this.selectedUser.role)

            const viewModal = bootstrap.Modal.getInstance(document.getElementById('roleSwitcherUserModal'))
            if (viewModal)
                viewModal.hide()
        },

    }
}
</script>
<style scoped>
.full-width-btn {
    width: 100%;
    background: transparent;
    border: 1px solid #007bff; /* рамка синяя */
    color: #007bff; /* текст синий */
    padding: 1rem;
}

</style>
