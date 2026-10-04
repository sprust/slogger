import {defineStore} from "pinia";

interface TraceAggregatorTreeSettingsStoreInterface {
    showPid: boolean,
}

export const useTraceAggregatorTreeSettingsStore = defineStore('traceAggregatorTreeSettingsStore', {
    state: (): TraceAggregatorTreeSettingsStoreInterface => {
        return {
            showPid: false,
        }
    },
})
