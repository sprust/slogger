import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";
// @ts-ignore // todo
import {Node} from "@vue-flow/core/dist/types/node";
// @ts-ignore // todo
import {Edge} from "@vue-flow/core/dist/types/edge";
import {ProfilingTreeBuilder} from "../components/pages/trace-aggregator/components/profiling/utils/treeBuilder.ts";
import {FlowBuilder} from "../components/pages/trace-aggregator/components/profiling/utils/flowBuilder.ts";
import {MetricsBuilder} from "../components/pages/trace-aggregator/components/profiling/utils/metricsBuilder.ts";

type Parameters = AdminApi.TraceAggregatorTracesProfilingDetail.RequestParams
export type ProfilingItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data'][number]

export interface FlowItems {
    nodes: Array<Node>,
    edges: Array<Edge>,
}

export interface ProfilingMetrics {
    numberOfCalls: number
    waitTimeInUs: number
    cpuTime: number
    memoryUsageInBytes: number
    peakMemoryUsageInBytes: number
    totalCount: number
    hardestItemIds: Array<string>
}

interface State {
    loading: boolean,
    parameters: Parameters,
    profilingItems: Array<ProfilingItem>,
    selectedItem: ProfilingItem | null,
    profilingTreeFilterPrev: string, // crutch
    profilingTreeFilter: string,
    profilingTree: Array<ProfilingTreeNode>,
    profilingMetrics: ProfilingMetrics,
    profilingMetricsSetting: {
        showNumberOfCalls: boolean
        showWaitTimeInUs: boolean
        showCpuTime: boolean
        showMemoryUsageInBytes: boolean
        showPeakMemoryUsageInBytes: boolean
    },
    flowItems: FlowItems,
}

export type ProfilingTreeNode = {
    key: string,
    label: string,
    children: null | Array<ProfilingTreeNode>,
    disabled: boolean
}

export const traceAggregatorProfilingStore = createStore<State>({
    state: {
        loading: true,
        parameters: {} as Parameters,
        profilingItems: new Array<ProfilingItem>,
        selectedItem: null as ProfilingItem | null,
        profilingTreeFilterPrev: '',
        profilingTreeFilter: '',
        profilingTree: [] as Array<ProfilingTreeNode>,
        profilingMetrics: {
            numberOfCalls: 0,
            waitTimeInUs: 0,
            cpuTime: 0,
            memoryUsageInBytes: 0,
            peakMemoryUsageInBytes: 0,
            totalCount: 0,
            hardestItemIds: []
        },
        profilingMetricsSetting: {
            showNumberOfCalls: true,
            showWaitTimeInUs: true,
            showCpuTime: true,
            showMemoryUsageInBytes: true,
            showPeakMemoryUsageInBytes: true,
        },
        flowItems: {
            nodes: [],
            edges: [],
        },
    } as State,
    mutations: {
        setProfilingItems(state: State, profilingItems: Array<ProfilingItem>) {
            state.profilingTreeFilterPrev = ''
            state.profilingTreeFilter = ''
            state.selectedItem = null
            state.profilingMetrics.hardestItemIds = []
            state.flowItems = {
                nodes: [],
                edges: [],
            }

            if (profilingItems.length > 1) {
                state.profilingItems = [
                    {
                        id: '-1',
                        call: 'root',
                        number_of_calls: 0,
                        wait_time_in_us: 0,
                        cpu_time: 0,
                        memory_usage_in_bytes: 0,
                        peak_memory_usage_in_bytes: 0,
                        callables: profilingItems,
                    }
                ]
            } else {
                state.profilingItems = profilingItems
            }

            state.profilingTree = (new ProfilingTreeBuilder()).build(state.profilingItems)
        },
        buildProfilingTree(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingTree = []

                return
            }

            state.profilingMetrics = (new MetricsBuilder()).build(item.callables)
        },
        setSelectedProfilingItem(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingTreeFilterPrev = ''
                state.profilingTreeFilter = ''
                state.selectedItem = null
                state.flowItems = {
                    nodes: [],
                    edges: [],
                }

                return;
            }

            state.selectedItem = item

            const flow = (new FlowBuilder([item])).build()

            state.flowItems.nodes = flow.nodes
            state.flowItems.edges = flow.edges

            state.profilingMetrics = (new MetricsBuilder()).build(state.selectedItem.callables)
        },
        calculateHardestFlow(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingMetrics.hardestItemIds = []

                return;
            }

            state.profilingMetrics = (new MetricsBuilder()).build(item.callables)
        },
        setProfilingTreeFilter(state: State, value: string) {
            state.profilingTreeFilter = value
        },
    },
    actions: {
        findProfiling(
            {commit, state}: { commit: any, state: State },
            parameters: Parameters
        ) {
            state.loading = true

            state.profilingItems = []

            state.parameters = parameters

            ApiContainer.get().traceAggregatorTracesProfilingDetail(parameters.traceId)
                .then(response => {
                    commit('setProfilingItems', response.data.data)
                })
                .catch(error => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
        clearProfiling({commit}: { commit: any }) {
            commit('setProfilingItems', [])
        },
        setProfilingItems({commit}: { commit: any }, profilingItems: Array<ProfilingItem>) {
            commit('setProfilingItems', profilingItems)
        },
        setSelectedProfilingItem({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('setSelectedProfilingItem', item)
        },
        buildProfilingTree({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('buildProfilingTree', item)
        },
        calculateHardestFlow({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('buildProfilingTree', item)
        },
        setProfilingTreeFilter({commit}: { commit: any }, value: string) {
            commit('setProfilingTreeFilter', value)
        },
    },
})

export const traceAggregatorProfilingStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorProfilingStore(): Store<State> {
    return baseUseStore(traceAggregatorProfilingStoreInjectionKey)
}
