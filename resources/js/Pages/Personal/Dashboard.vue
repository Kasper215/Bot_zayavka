<script setup>
import Layout from "@/Layouts/Layout.vue";
import { Link, Head, router } from "@inertiajs/vue3";
import { onMounted, ref } from 'vue';

const props = defineProps({
    leads: Array,
    user: Object
});

const getStatusColor = (status) => {
    const statuses = {
        'new': '#60a5fa',
        'pending_payment': '#f59e0b',
        'paid': '#10b981',
        'in_progress': '#a855f7',
        'completed': '#10b981',
        'rejected': '#ef4444'
    };
    return statuses[status] || '#94a3b8';
};

const getStatusText = (status) => {
    const statuses = {
        'new': 'Новая заявка',
        'pending_payment': 'Ожидает оплаты',
        'paid': 'Оплачено',
        'in_progress': 'В работе',
        'completed': 'Завершена',
        'rejected': 'Отклонена'
    };
    return statuses[status] || status;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const refresh = () => {
    router.reload({ only: ['leads'] });
};
</script>

<template>
    <Layout>
        <Head title="Личный кабинет | BioBook" />

        <div class="personal-container">
            <header class="personal-header">
                <div class="header-top-row">
                    <Link href="/" class="back-link">
                        <span class="back-icon">←</span>
                        Вернуться на главную
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="logout-btn">
                        Выйти
                    </Link>
                </div>
                <div class="welcome-box">
                    <span class="greeting">Здравствуйте,</span>
                    <h1 class="user-name">{{ user.name }}</h1>
                </div>
                <div class="header-decoration"></div>
            </header>

            <section class="leads-section">
                <div class="section-title-row">
                    <h2 class="section-title">Ваши заказы</h2>
                    <span class="lead-count">{{ leads.length }}</span>
                </div>

                <div v-if="leads.length === 0" class="empty-state glass-panel">
                    <div class="empty-icon">📂</div>
                    <h3>У вас пока нет заказов</h3>
                    <p>Самое время начать историю своей первой книги!</p>
                    <Link href="/" class="create-btn">Создать BioBook 🚀</Link>
                </div>

                <div v-else class="leads-grid">
                    <div v-for="lead in leads" :key="lead.id" class="lead-card glass-panel">
                        <div class="lead-header">
                            <span class="lead-date">{{ formatDate(lead.created_at) }}</span>
                            <div class="status-badge" :style="{ backgroundColor: getStatusColor(lead.status) + '20', color: getStatusColor(lead.status), borderColor: getStatusColor(lead.status) + '40' }">
                                {{ getStatusText(lead.status) }}
                            </div>
                        </div>

                        <div class="lead-body">
                            <div class="info-row">
                                <span class="label">Жанр:</span>
                                <span class="value">{{ lead.service_type }}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Объем:</span>
                                <span class="value">{{ lead.volume_stage }}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Контакты:</span>
                                <span class="value">{{ lead.contacts }}</span>
                            </div>
                            
                            <div class="price-box" v-if="lead.calc_price">
                                <span class="price-label">Сумма заказа:</span>
                                <span class="price-value">{{ lead.calc_price }}</span>
                            </div>

                            <div v-if="lead.extra" class="extra-info">
                                <span class="label">Доп. информация:</span>
                                <p class="text">{{ lead.extra }}</p>
                            </div>

                            <div v-if="lead.files && lead.files.length > 0" class="files-info">
                                <span class="label">Прикреплено файлов:</span>
                                <span class="value">{{ lead.files.length }} шт.</span>
                            </div>

                            <div v-if="lead.manager_notes" class="manager-notes">
                                <span class="notes-label">Заметка от менеджера:</span>
                                <p class="notes-text">{{ lead.manager_notes }}</p>
                            </div>
                        </div>

                        <div class="lead-footer">
                            <button v-if="lead.status === 'pending_payment'" class="action-btn pay-btn">Оплатить</button>
                            <button class="action-btn details-btn" @click="refresh">Обновить статус</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </Layout>
</template>

<style scoped>
.personal-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

.personal-header {
    margin-bottom: 50px;
    position: relative;
}

.header-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.logout-btn {
    background: transparent;
    border: none;
    color: #ef4444;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.logout-btn:hover {
    opacity: 1;
}

.back-link:hover {
    color: #60a5fa;
    transform: translateX(-5px);
}

.back-icon {
    font-size: 1.2rem;
}

.greeting {
    color: #94a3b8;
    font-size: 1.1rem;
    font-weight: 300;
}

.user-name {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
    background: linear-gradient(135deg, #fff 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.header-decoration {
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #60a5fa, #a855f7);
    margin-top: 15px;
    border-radius: 2px;
}

.section-title-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.section-title {
    font-size: 1.5rem;
    color: #fff;
    font-weight: 700;
    margin: 0;
}

.lead-count {
    background: rgba(96, 165, 250, 0.2);
    color: #60a5fa;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 700;
}

.glass-panel {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 30px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #fff;
    margin-bottom: 10px;
}

.empty-state p {
    color: #94a3b8;
    margin-bottom: 30px;
}

.create-btn {
    display: inline-block;
    background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    color: #fff;
    text-decoration: none;
    padding: 12px 30px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.create-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
}

.leads-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.lead-card {
    transition: all 0.3s ease;
}

.lead-card:hover {
    transform: translateY(-5px);
    border-color: rgba(96, 165, 250, 0.2);
}

.lead-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.lead-date {
    font-size: 0.85rem;
    color: #64748b;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid transparent;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.info-row .label {
    color: #64748b;
}

.info-row .value {
    color: #fff;
    font-weight: 600;
    text-align: right;
}

.extra-info {
    margin-top: 15px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
}

.extra-info .label, .files-info .label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.extra-info .text {
    font-size: 0.85rem;
    color: #cbd5e1;
    margin: 0;
}

.files-info {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.files-info .value {
    color: #10b981;
    font-weight: 700;
}

.price-box {
    background: rgba(0, 0, 0, 0.2);
    padding: 12px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.price-label {
    color: #64748b;
    font-size: 0.8rem;
}

.price-value {
    color: #60a5fa;
    font-weight: 800;
}

.manager-notes {
    background: rgba(245, 158, 11, 0.05);
    border-left: 3px solid #f59e0b;
    padding: 10px 15px;
    margin-top: 15px;
}

.notes-label {
    font-size: 0.7rem;
    color: #f59e0b;
    text-transform: uppercase;
    font-weight: 900;
}

.notes-text {
    font-size: 0.85rem;
    color: #d1d5db;
    margin: 5px 0 0 0;
}

.lead-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 20px;
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.action-btn {
    flex: 1;
    border: none;
    padding: 10px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.details-btn {
    background: rgba(255, 255, 255, 0.05);
    color: #94a3b8;
}

.details-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.pay-btn {
    background: #60a5fa;
    color: #fff;
}

.pay-btn:hover {
    background: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

@media (max-width: 600px) {
    .leads-grid {
        grid-template-columns: 1fr;
    }
    .user-name {
        font-size: 2rem;
    }
}
</style>
