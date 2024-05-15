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
import {FlowBuilder} from "../components/pages/trace-aggregator/components/profiling/utils/flowBuilder.ts";
import {MetricsBuilder} from "../components/pages/trace-aggregator/components/profiling/utils/metricsBuilder.ts";
import {
    IndicatorsCollector
} from "../components/pages/trace-aggregator/components/profiling/utils/indicatorsCollector.ts";

type Parameters = AdminApi.TraceAggregatorTracesProfilingDetail.RequestParams

export type Profiling = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data']
export type ProfilingItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data']['items'][number]
export type ProfilingDataItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data']['items'][number]['data'][number]

export interface FlowItems {
    nodes: Array<Node>,
    edges: Array<Edge>,
}

export interface ProfilingMetrics {
    totalCount: number
    hardestItemIds: Array<string>
}

interface State {
    loading: boolean,
    parameters: Parameters,
    profiling: Profiling,
    showTree: boolean,
    selectedItem: ProfilingItem | null,
    profilingIndicators: Array<string>,
    profilingTreeFilterPrev: string, // crutch
    profilingTreeFilter: string,
    profilingMetrics: ProfilingMetrics,
    profilingMetricsSetting: {
        hardestItemIndicatorName: string,
        showProfilingIndicators: Array<string>
    },
    flowItems: FlowItems,
}

export type ProfilingTreeNode = {
    key: string,
    label: string,
    disabled: boolean,
    leaf?: boolean,
}

export const traceAggregatorProfilingStore = createStore<State>({
    state: {
        loading: true,
        parameters: {} as Parameters,
        profiling: {
            main_caller: '',
            items: []
        },
        showTree: true,
        selectedItem: null as ProfilingItem | null,
        profilingIndicators: [],
        profilingTreeFilterPrev: '',
        profilingTreeFilter: '',
        profilingMetrics: {
            totalCount: 0,
            hardestItemIds: []
        },
        profilingMetricsSetting: {
            hardestItemIndicatorName: '',
            showProfilingIndicators: [],
        },
        flowItems: {
            nodes: [],
            edges: [],
        },
    } as State,
    mutations: {
        setProfiling(state: State, profiling: Profiling) {
            state.profilingTreeFilterPrev = ''
            state.profilingTreeFilter = ''
            state.selectedItem = null
            state.profilingMetrics.hardestItemIds = []
            state.flowItems = {
                nodes: [],
                edges: [],
            }

            state.profiling = profiling

            state.profilingIndicators = (new IndicatorsCollector()).collect(state.profiling.items)

            if (state.profilingIndicators.length) {
                state.profilingMetricsSetting.showProfilingIndicators = [state.profilingIndicators[0]]
                state.profilingMetricsSetting.hardestItemIndicatorName = state.profilingIndicators[0] ?? ''
            } else {
                state.profilingMetricsSetting.showProfilingIndicators = []
                state.profilingMetricsSetting.hardestItemIndicatorName = ''
            }
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

            state.profilingMetrics = (new MetricsBuilder(
                state.profilingMetricsSetting.hardestItemIndicatorName,
                item.callable,
                state.profiling.items
            )).build()

            const flow = (new FlowBuilder(state.profiling.items, state.profilingMetrics.hardestItemIds))
                .build(state.selectedItem.calling)

            state.flowItems.nodes = flow.nodes
            state.flowItems.edges = flow.edges
        },
        calculateProfilingMetrics(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingMetrics.hardestItemIds = []

                return;
            }

            state.profilingMetrics = (new MetricsBuilder(
                state.profilingMetricsSetting.hardestItemIndicatorName,
                item.callable,
                state.profiling.items
            )).build()
        },
        setProfilingTreeFilter(state: State, value: string) {
            state.profilingTreeFilter = value
        },
        switchShowTree(state: State) {
            state.showTree = !state.showTree
        },
    },
    actions: {
        findProfiling(
            {commit, state}: { commit: any, state: State },
            {traceId}: { traceId: string }
        ) {
            state.loading = true

            state.profiling = {
                main_caller: '',
                items: []
            }

            state.parameters = {
                traceId: traceId
            }

            ApiContainer.get()
                .traceAggregatorTracesProfilingDetail(
                    traceId,
                )
                .then(response => {
                    commit('setProfiling', response.data.data)
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
        calculateProfilingMetrics({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('calculateProfilingMetrics', item)
        },
        setProfilingTreeFilter({commit}: { commit: any }, value: string) {
            commit('setProfilingTreeFilter', value)
        },
        switchShowTree({commit}: { commit: any }) {
            commit('switchShowTree')
        },
    },
})

export const traceAggregatorProfilingStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorProfilingStore(): Store<State> {
    return baseUseStore(traceAggregatorProfilingStoreInjectionKey)
}
