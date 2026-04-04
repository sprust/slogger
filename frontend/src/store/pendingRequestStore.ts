import {defineStore} from "pinia";
import type {AdminApi} from "../api-schema/admin-api-schema.ts";

type PendingTraceDynamicIndex = AdminApi.TraceAggregatorDynamicIndexesList.ResponseBody['data'][number]

interface PendingRequestStoreInterface {
    visible: boolean
    cancelRequested: boolean
    data: PendingTraceDynamicIndex | null
}

export const usePendingRequestStore = defineStore('pendingRequestStore', {
    state: (): PendingRequestStoreInterface => {
        return {
            visible: false,
            cancelRequested: false,
            data: null,
        }
    },
    actions: {
        open(data: PendingTraceDynamicIndex | null = null) {
            this.visible = true
            this.cancelRequested = false
            this.data = data
        },
        setData(data: PendingTraceDynamicIndex | null = null) {
            this.data = data
        },
        requestCancel() {
            this.cancelRequested = true
        },
        close() {
            this.visible = false
            this.cancelRequested = false
            this.data = null
        },
    },
})
