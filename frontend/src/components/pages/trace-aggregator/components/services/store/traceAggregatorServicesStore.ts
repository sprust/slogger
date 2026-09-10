import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceAggregatorService = AdminApi.ServicesList.ResponseBody['data'][number];

interface TraceAggregatorServicesStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<TraceAggregatorService>
}

export const useTraceAggregatorServicesStore = defineStore('traceAggregatorServicesStore', {
    /**
     * `loading` is a request in flight and `loaded` is whether the list has ever arrived.
     *
     * One flag was doing both jobs: it started as true, meaning "nobody has fetched this
     * yet", and the aggregator fetched only while it was true. Anything else reading it
     * as a request in flight — which is what it is called — concluded somebody was
     * already fetching and left the list alone, so a service behind an incident had no
     * name but its id.
     */
    state: (): TraceAggregatorServicesStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<TraceAggregatorService>
        }
    },
    actions: {
        async findServices() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().servicesList()
                    .then(response => {
                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        }
    },
})
