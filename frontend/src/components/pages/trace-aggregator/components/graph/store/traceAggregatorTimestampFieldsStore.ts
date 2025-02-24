import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceTimestampField = AdminApi.TraceAggregatorTraceMetricsFieldsList.ResponseBody['data'][number]

interface TraceAggregatorTimestampFieldsStoreInterface {
    loaded: boolean,
    fields: Array<TraceTimestampField>
    selectedFields: Array<string>,
}

export const useTraceAggregatorTimestampFieldsStore = defineStore('traceAggregatorTimestampFieldsStore', {
    state: (): TraceAggregatorTimestampFieldsStoreInterface => {
        return {
            loaded: false,
            fields: new Array<TraceTimestampField>(),
            selectedFields: new Array<string>(),
        }
    },
    actions: {
        async findTimestampFields() {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTraceMetricsFieldsList()
                    .then(response => {
                        this.fields = response.data.data
                        this.selectedFields = [this.fields[0].value]

                        this.loaded = true
                    })
            )
        },
    },
})
