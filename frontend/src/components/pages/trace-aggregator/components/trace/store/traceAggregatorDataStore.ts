import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceAggregatorDetail = AdminApi.TraceAggregatorTracesDetail.ResponseBody['data'];
export type TraceAggregatorDetailData = AdminApi.TraceAggregatorTracesDetail.ResponseBody['data']['data'];

type TraceDataItem = {
    loaded: boolean
    data: TraceAggregatorDetailData
}

interface TraceDataItems {
    [key: string]: TraceDataItem;
}

interface TraceAggregatorDataStoreInterface {
    dataItems: TraceDataItems,
}

export const useTraceAggregatorDataStore = defineStore('traceAggregatorDataStore', {
    state: (): TraceAggregatorDataStoreInterface => {
        return {
            dataItems: {},
        }
    },
    actions: {
        async findTraceData(traceId: string) {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesDetail(traceId)
                    .then(response => {
                        // @ts-ignore recursion
                        this.setData(traceId, response.data)
                    })
            )
        },
        setData(traceId: string, trace: TraceAggregatorDetail) {
            this.dataItems[traceId] = {
                loaded: true,
                data: trace.data
            }
        },
    },
})
