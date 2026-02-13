<template>
    <div>

        <div class="d-flex mb-2">
            <!-- Кнопка вызова модалки -->
            <button
                style="font-size:12px;"
                class="btn btn-secondary" @click="openFilter">Фильтр</button>

            <!-- Dropdown сортировки -->
            <div class="dropdown d-inline-block ms-2">
                <button
                    style="font-size:12px;"
                    class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                    {{ sortableFields[sort.field].slice(0, 17) }}
                    <span v-if="sortableFields[sort.field].length>17">...</span>
                    (<span v-if="sort.direction==='asc'"><i class="fa-solid fa-arrow-down"></i></span>
                    <span v-if="sort.direction==='desc'"><i class="fa-solid fa-arrow-up"></i></span>)
                </button>
                <ul class="dropdown-menu">
                    <li v-for="(name, field ) in sortableFields" :key="field">
                        <a class="dropdown-item" @click="changeSort(field)">
                            {{ name }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>


        <!-- Модалка фильтрации -->
        <div class="modal fade" id="userFilterModal" tabindex="-1">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <form @submit.prevent="applyFilters">
                        <div class="modal-header">
                            <h5 class="modal-title">Фильтры пользователей</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Имя -->
                            <div class="form-floating mb-2">
                                <input v-model="filters.name" class="form-control" id="nameInput" placeholder="Имя"/>
                                <label for="nameInput">Имя</label>
                            </div>

                            <!-- ФИО из Telegram -->
                            <div class="form-floating mb-2">
                                <input v-model="filters.fio_from_telegram" class="form-control" id="fioInput"
                                       placeholder="ФИО из Telegram"/>
                                <label for="fioInput">ФИО из Telegram</label>
                            </div>

                            <!-- Email -->
                            <div class="form-floating mb-2">
                                <input v-model="filters.email" class="form-control" id="emailInput"
                                       placeholder="Email"/>
                                <label for="emailInput">Email</label>
                            </div>

                            <!-- Telegram chat id -->
                            <div class="form-floating mb-2">
                                <input v-model="filters.telegram_chat_id" class="form-control" id="tgInput"
                                       placeholder="Telegram chat id"/>
                                <label for="tgInput">Telegram chat id</label>
                            </div>

                            <!-- Роль -->
                            <div class="form-floating mb-2">
                                <select v-model="filters.role" class="form-select" id="roleSelect">
                                    <option value="">Все</option>
                                    <option value="0">Пользователь</option>
                                    <option value="1">Младший администратор</option>
                                    <option value="2">Поставщик</option>
                                    <option value="3">Администратор</option>
                                    <option value="4">Суперадмин</option>
                                </select>
                                <label for="roleSelect">Роль</label>
                            </div>

                            <!-- Процент -->
                            <div class="form-floating mb-2">
                                <input type="number" v-model="filters.percent" class="form-control" id="percentInput"
                                       placeholder="Процент"/>
                                <label for="percentInput">Процент</label>
                            </div>

                            <!-- Работает -->
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="isWorkCheck"
                                       v-model="filters.is_work"/>
                                <label class="form-check-label" for="isWorkCheck">Работает</label>
                            </div>

                            <!-- Верификация email -->
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="verifiedCheck"
                                       v-model="filters.email_verified"/>
                                <label class="form-check-label" for="verifiedCheck">Email подтверждён</label>
                            </div>

                            <!-- Заблокирован -->
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="blockedCheck"
                                       v-model="filters.blocked"/>
                                <label class="form-check-label" for="blockedCheck">Заблокирован</label>
                            </div>

                            <h6>Отображаемые поля</h6>
                            <div v-for="(label, field) in sortableFields" :key="field"
                                 class="form-check form-switch mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    v-model="field_visible[field]"
                                    :id="`switch-${field}`"
                                />
                                <label class="form-check-label" :for="`switch-${field}`">
                                    {{ label }}
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Применить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserFilter',
    data() {
        return {
            field_visible: [],
            filters: {
                name: '',
                fio_from_telegram: '',
                email: '',
                telegram_chat_id: '',
                role: '',
                percent: '',
                is_work: false,
                email_verified: false,
                blocked: false
            },
            sort: {
                field: 'id',
                direction: 'asc'
            },
            sortableFields: {
                id: "№",
                name: "Имя",
                fio_from_telegram: "ФИО из Telegram",
                email: "Электронная почта",
                telegram_chat_id: "ID чата Telegram",
                role: "Роль",
                percent: "Процент",
                is_work: "Работает(да\\нет)",
                email_verified_at: "Дата подтверждения email",
                blocked_at: "Дата блокировки",
                created_at: "Дата создания",
                updated_at: "Дата обновления данных"
            }

        }
    },
    created() {
        let tmpVisibleFields = [ 'name', 'email']
        for (const field in this.sortableFields) {
            this.field_visible[field] = tmpVisibleFields.indexOf(field) !== -1
        }
    },
    methods: {
        openFilter() {
            new bootstrap.Modal(document.getElementById('userFilterModal')).show()
        },
        changeSort(field) {
            if (this.sort.field === field) {
                this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
            } else {
                this.sort.field = field
                this.sort.direction = 'asc'
            }

            const payload = {
                filters: this.filters,
                sort: this.sort
            }
            this.$emit('apply-filters', payload)
        },
        applyFilters() {
            const payload = {
                filters: this.filters,
                sort: this.sort,
                field_visible: this.field_visible
            }

            this.$emit('apply-filters', payload)
            bootstrap.Modal.getInstance(document.getElementById('userFilterModal')).hide()
        }
    }
}
</script>

