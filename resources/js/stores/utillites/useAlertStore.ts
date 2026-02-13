import { defineStore } from 'pinia'

export const useAlertStore = defineStore('alert', {
    state: () => ({
        message: null as string | null,
        type: null as 'success' | 'error' | 'info' | null,
        visible: false as boolean
    }),
    actions: {
        show(message: string, type: 'success' | 'error' | 'info' = 'info') {
            this.message = message
            this.type = type
            this.visible = true

            // авто‑скрытие через 5 секунд
            setTimeout(() => {
                this.hide()
            }, 5000)
        },
        hide() {
            this.visible = false
            this.message = null
            this.type = null
        }
    }
})
