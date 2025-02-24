import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceAggregatorService = AdminApi.ServicesList.ResponseBody['data'][number];

interface TraceAggregatorServicesStoreInterface {
    loading: boolean
    items: Array<TraceAggregatorService>
}

export const useTraceAggregatorServicesStore = defineStore('traceAggregatorServicesStore', {
    state: (): TraceAggregatorServicesStoreInterface => {
        return {
            loading: true,
            items: [] as Array<TraceAggregatorService>
        }
    },
    actions: {
        async findServices() {
            this.loading = true

            return await handleApiRequest(
                ApiContainer.get().servicesList()
                    .then(response => {
                        this.items = response.data.data
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        }
    },
})
