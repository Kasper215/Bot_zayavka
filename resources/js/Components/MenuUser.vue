<script setup>
import BookLeadForm from "@/Components/Users/Forms/BookLeadForm.vue";
</script>

<template>
    <div class="menu-component">
        <div class="tab-content">
            <BookLeadForm />
        </div>
    </div>
</template>

<style scoped>
.menu-component {
    max-width: 800px;
    margin: 0 auto;
}

.tabs-glass {
    display: flex;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 6px;
    gap: 4px;
}

.tab-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 12px;
    border: none;
    background: transparent;
    color: #94a3b8;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
}

.t-icon {
    font-size: 1.5rem;
}

.tab-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.03);
}

.tab-btn.active {
    background: #60a5fa;
    color: #fff;
    box-shadow: 0 4px 20px rgba(96, 165, 250, 0.4);
}

.standalone-container {
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from { opacity: 0; transform: scale(0.98); }
.fade-leave-to { opacity: 0; transform: scale(1.02); }

@media (max-width: 480px) {
    .t-text { display: none; }
    .tab-btn { padding: 14px; }
    .t-icon { font-size: 1.2rem; }
}
</style>
