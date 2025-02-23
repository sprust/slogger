import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../../../utils/helpers.ts";
import {defineStore} from "pinia";

export type TraceTimestampPeriod = AdminApi.TraceAggregatorTraceTimestampPeriodsList.ResponseBody['data'][number];
export type TraceTimestampStep = AdminApi.TraceAggregatorTraceTimestampPeriodsList.ResponseBody['data'][number]['timestamps'][number];

interface traceAggregatorTimestampPeriodStoreInterface {
    loaded: boolean,

    timestampPeriods: Array<TraceTimestampPeriod>
    timestampSteps: Array<TraceTimestampStep>

    selectedTimestampPeriod: string,
    selectedTimestampStep: string,
}

export const useTraceAggregatorTimestampPeriodStore = defineStore('traceAggregatorTimestampPeriodStore', {
    state: (): traceAggregatorTimestampPeriodStoreInterface => {
        return {
            loaded: false,
            timestampPeriods: new Array<TraceTimestampPeriod>(),
            timestampSteps: new Array<TraceTimestampStep>(),
            selectedTimestampPeriod: '',
            selectedTimestampStep: '',
        }
    },
    actions: {
        findTimestampPeriods() {
            ApiContainer.get().traceAggregatorTraceTimestampPeriodsList()
                .then(response => {
                    this.setPeriods(response.data.data)
                    this.freshTimestampSteps()

                    this.loaded = true
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        freshTimestampSteps() {
            const selectedTimestampPeriod = this.timestampPeriods.find((periodItem: TraceTimestampPeriod) => {
                return periodItem.period.value === this.selectedTimestampPeriod
            })

            this.timestampSteps = selectedTimestampPeriod ? selectedTimestampPeriod.timestamps : []

            this.selectedTimestampStep = this.timestampSteps[0].value        },
        setPeriods(periods: Array<TraceTimestampPeriod>) {
            this.timestampPeriods = periods

            this.selectedTimestampPeriod = this.timestampPeriods[0].period.value
        },
    },
})
