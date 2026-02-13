<template>
    <nav v-if="pagination" aria-label="Page navigation" class="my-3">
        <ul class="pagination justify-content-center align-items-center">
            <!-- Кнопка "Назад" -->
            <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                <button
                    type="button"
                    class="page-link"
                    :disabled="!pagination.prev_page_url"
                    @click="changePage(pagination.prev_page_url)"
                >
                    &laquo;
                </button>
            </li>

            <!-- Текущая страница / всего -->
            <li class="page-item disabled">
        <span class="page-link">
          {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
            </li>

            <!-- Кнопка "Вперёд" -->
            <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                <button
                    type="button"
                    class="page-link"
                    :disabled="!pagination.next_page_url"
                    @click="changePage(pagination.next_page_url)"
                >
                    &raquo;
                </button>
            </li>
        </ul>
    </nav>
</template>

<script>
export default {
    name: 'Pagination',
    props: {
        pagination: {
            type: Object,
            required: true
        }
    },
    methods: {
        changePage(url) {
            if (!url) return
            this.$emit('page-changed', url)
        }
    }
}
</script>

<style scoped>
.page-item.disabled .page-link {
    cursor: not-allowed;
}
</style>
