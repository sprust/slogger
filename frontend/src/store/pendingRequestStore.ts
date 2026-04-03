import {defineStore} from "pinia";

interface PendingRequestStoreInterface {
    visible: boolean
    cancelRequested: boolean
}

export const usePendingRequestStore = defineStore('pendingRequestStore', {
    state: (): PendingRequestStoreInterface => {
        return {
            visible: false,
            cancelRequested: false,
        }
    },
    actions: {
        open() {
            this.visible = true
            this.cancelRequested = false
        },
        requestCancel() {
            this.cancelRequested = true
        },
        close() {
            this.visible = false
            this.cancelRequested = false
        },
    },
})
