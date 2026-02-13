<template>
    <transition name="fade">
        <div class="wrapper-alert">
            <div v-if="alertStore.visible" class="global-alert" :class="alertClass">
                {{ alertStore.message }}
                <button class="close-btn" @click="alertStore.hide">×</button>
            </div>
        </div>

    </transition>
</template>

<script>
import { useAlertStore } from '@/stores/utillites/useAlertStore'

export default {
    name: 'GlobalAlert',
    setup() {
        const alertStore = useAlertStore()


        return { alertStore }
    },
    computed:{
        alertClass() {
            switch (this.alertStore.type) {
                case 'success': return 'alert-success'
                case 'error': return 'alert-error'
                default: return 'alert-info'
            }
        }
    }
}
</script>

<style scoped>

.wrapper-alert{
    position: fixed;
    top: 20px;
    right: 0px;
    padding: 10px;
    z-index: 9999;
}
.global-alert {
    padding: 12px 20px;
    border-radius: 6px;
    color: #fff;
    font-weight: 500;

}
.alert-success { background-color: #28a745; }
.alert-error { background-color: #dc3545; }
.alert-info { background-color: #007bff; }
.close-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 18px;
    margin-left: 10px;
    cursor: pointer;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
