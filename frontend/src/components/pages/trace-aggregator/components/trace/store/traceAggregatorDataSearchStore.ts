import {defineStore} from "pinia";

interface TraceAggregatorDataSearchStoreInterface {
    query: string,
    inValues: boolean,
}

export const useTraceAggregatorDataSearchStore = defineStore('traceAggregatorDataSearchStore', {
    state: (): TraceAggregatorDataSearchStoreInterface => {
        return {
            query: '',
            inValues: false,
        }
    },
})
