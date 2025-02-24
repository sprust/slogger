import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceDynamicIndex = AdminApi.TraceAggregatorDynamicIndexesList.ResponseBody['data'][number];
export type TraceDynamicIndexStats = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']
export type TraceDynamicIndexInfo = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']['indexes_in_process'][number]

interface TraceDynamicIndexesStoreInterface {
    started: boolean,
    loading: boolean
    traceDynamicIndexStats: TraceDynamicIndexStats,
    traceDynamicIndexes: Array<TraceDynamicIndex>
}

export const useTraceDynamicIndexesStore = defineStore('traceDynamicIndexesStore', {
    state: (): TraceDynamicIndexesStoreInterface => {
        return {
            started: false,
            loading: false,
            traceDynamicIndexStats: {} as TraceDynamicIndexStats,
            traceDynamicIndexes: [] as Array<TraceDynamicIndex>
        }
    },
    actions: {
        async findTraceDynamicIndexes() {
            this.loading = true

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorDynamicIndexesList()
                    .then(response => {
                        this.traceDynamicIndexes = response.data.data
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findTraceDynamicIndexStats() {
            this.started = true

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorDynamicIndexesStatsList()
                    .then(response => {
                        this.traceDynamicIndexStats = response.data.data
                    })
            )
        },
        async deleteTraceDynamicIndex(id: string) {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorDynamicIndexesDelete(id)
                    .then(() => {
                        this.traceDynamicIndexes = this.traceDynamicIndexes.filter(
                            (index: TraceDynamicIndex) => index.id !== id
                        )
                    })
            )
        },
    },
})
