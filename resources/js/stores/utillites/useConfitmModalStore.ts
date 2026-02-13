// stores/useModalStore.js
import { defineStore } from 'pinia'

export const useModalStore = defineStore('modal', {
    state: () => ({
        show: false,
        message: '',
        onConfirm: null,
        onReject: null,
    }),
    actions: {
        open(message, onConfirm, onReject) {
            this.message = message
            this.onConfirm = onConfirm
            this.onReject = onReject
            this.show = true
        },
        close() {
            this.show = false
            this.message = ''
            this.onConfirm = null
            this.onReject = null
        },
        confirm() {
            if (this.onConfirm) this.onConfirm()
            this.close()
        },
        reject() {
            if (this.onReject) this.onReject()
            this.close()
        },
    },
})
