import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type TraceCleanerProcessItem = AdminApi.TraceCleanerProcessesList.ResponseBody['data'][number];

interface TraceCleanerStoreInterface {
    loading: boolean
    processes: Array<TraceCleanerProcessItem>
}

export const useTraceCleanerStore = defineStore('traceCleanerStore', {
    state: (): TraceCleanerStoreInterface => {
        return {
            loading: true,
            processes: [] as Array<TraceCleanerProcessItem>,
        }
    },
    actions: {
        async find() {
            this.loading = true

            return await handleApiRequest(
                ApiContainer.get().traceCleanerProcessesList()
                    .then(response => {
                        this.processes = response.data.data

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
    },
})
