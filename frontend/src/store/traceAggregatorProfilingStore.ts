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
import {
    IndicatorsCollector
} from "../components/pages/trace-aggregator/components/profiling/utils/indicatorsCollector.ts";

type Parameters = AdminApi.TraceAggregatorTracesProfilingDetail.RequestParams
export type ProfilingItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data'][number]
export type ProfilingDataItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data'][number]['data'][number]

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
    profilingItems: Array<ProfilingItem>,
    selectedItem: ProfilingItem | null,
    profilingIndicators: Array<string>,
    profilingTreeFilterPrev: string, // crutch
    profilingTreeFilter: string,
    profilingTree: Array<ProfilingTreeNode>,
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
    children: null | Array<ProfilingTreeNode>,
    disabled: boolean
}

export const traceAggregatorProfilingStore = createStore<State>({
    state: {
        loading: true,
        parameters: {} as Parameters,
        profilingItems: new Array<ProfilingItem>,
        selectedItem: null as ProfilingItem | null,
        profilingIndicators: [],
        profilingTreeFilterPrev: '',
        profilingTreeFilter: '',
        profilingTree: [] as Array<ProfilingTreeNode>,
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
        setProfilingItems(state: State, profilingItems: Array<ProfilingItem>) {
            state.profilingIndicators = []
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
                        data: [],
                        callables: profilingItems,
                    }
                ]
            } else {
                state.profilingItems = profilingItems
            }

            state.profilingIndicators = (new IndicatorsCollector()).collect(state.profilingItems)

            if (state.profilingIndicators.length) {
                state.profilingMetricsSetting.showProfilingIndicators = [state.profilingIndicators[0]]
                state.profilingMetricsSetting.hardestItemIndicatorName = state.profilingIndicators[0] ?? ''
            } else {
                state.profilingMetricsSetting.showProfilingIndicators = []
                state.profilingMetricsSetting.hardestItemIndicatorName = ''
            }

            state.profilingTree = (new ProfilingTreeBuilder()).build(state.profilingItems)
        },
        buildProfilingTree(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingTree = []

                return
            }

            state.profilingMetrics = (new MetricsBuilder(
                state.profilingMetricsSetting.hardestItemIndicatorName,
                item.callables
            )).build()
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

            state.profilingMetrics = (new MetricsBuilder(
                state.profilingMetricsSetting.hardestItemIndicatorName,
                state.selectedItem.callables
            )).build()
        },
        calculateHardestFlow(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingMetrics.hardestItemIds = []

                return;
            }

            state.profilingMetrics = (new MetricsBuilder(
                state.profilingMetricsSetting.hardestItemIndicatorName,
                item.callables
            )).build()
        },
        setProfilingTreeFilter(state: State, value: string) {
            state.profilingTreeFilter = value
        },
    },
    actions: {
        findProfiling(
            {commit, state}: { commit: any, state: State },
            {traceId, call}: { traceId: string, call: string | null }
        ) {
            state.loading = true

            state.profilingItems = []

            state.parameters = {
                traceId: traceId
            }

            ApiContainer.get()
                .traceAggregatorTracesProfilingDetail(
                    traceId,
                    call ? {call: call} : {}
                )
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
