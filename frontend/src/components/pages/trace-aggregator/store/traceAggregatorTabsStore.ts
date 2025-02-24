import {defineStore} from "pinia";

export const traceAggregatorTabs = {
    traces: 'traces',
    tree: 'tree',
    profiling: 'profiling',
}

interface TraceAggregatorTabsStoreInterface {
    currentTab: string,
}

export const useTraceAggregatorTabsStore = defineStore('traceAggregatorTabsStore', {
    state: (): TraceAggregatorTabsStoreInterface => {
        return {
            currentTab: traceAggregatorTabs.traces,
        }
    },
    actions: {
        setCurrentTab(tab: string) {
            this.currentTab = tab
        }
    },
})
