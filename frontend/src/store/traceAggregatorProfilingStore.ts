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
import {
    IndicatorsCollector
} from "../components/pages/trace-aggregator/components/profiling/utils/indicatorsCollector.ts";

type Parameters = AdminApi.TraceAggregatorTracesProfilingCreate.RequestParams
type Body = AdminApi.TraceAggregatorTracesProfilingCreate.RequestBody

export type Profiling = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']
export type ProfilingNode = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']['nodes'][number]
export type ProfilingNodeDataItem = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']['nodes'][number]['data'][number]

export interface FlowItems {
    nodes: Array<Node>,
    edges: Array<Edge>,
}

interface State {
    loading: boolean,
    parameters: Parameters,
    requestBody: Body,
    profiling: Profiling,
    showExcludedCallerPreviewDialog: boolean,
    excludedCallerPreview: string,
    showTree: boolean,
    selectedItem: ProfilingNode | null,
    profilingIndicators: Array<string>,
    showProfilingIndicators: Array<string>
    flowItems: FlowItems,
}

export type ProfilingTreeNode = {
    key: number,
    label: string,
    children: null | Array<ProfilingTreeNode>,
    primary: ProfilingNode,
}

export const traceAggregatorProfilingStore = createStore<State>({
    state: {
        loading: true,
        parameters: {} as Parameters,
        requestBody: {
            caller: null,
            excluded_callers: []
        } as Body,
        profiling: {
            nodes: []
        },
        showExcludedCallerPreviewDialog: false,
        excludedCallerPreview: '',
        showTree: true,
        selectedItem: null as ProfilingNode | null,
        profilingIndicators: [],
        showProfilingIndicators: [],
        flowItems: {
            nodes: [],
            edges: [],
        },
    } as State,
    mutations: {
        setProfiling(state: State, profiling: Profiling) {
            state.selectedItem = null
            state.flowItems = {
                nodes: [],
                edges: [],
            }

            state.profiling = profiling

            state.profilingIndicators = (new IndicatorsCollector()).collect(state.profiling.nodes)

            state.showProfilingIndicators = state.profilingIndicators.length
                ? [state.profilingIndicators[0]]
                : []
        },
        setSelectedProfilingItem(state: State, item: ProfilingNode | null) {
            if (!item) {
                state.selectedItem = null
                state.flowItems = {
                    nodes: [],
                    edges: [],
                }

                return;
            }

            state.selectedItem = item

            const flow = (new FlowBuilder(state.selectedItem)).build()

            state.flowItems.nodes = flow.nodes
            state.flowItems.edges = flow.edges
        },
        switchShowTree(state: State) {
            state.showTree = !state.showTree
        },
        setBodyCaller(state: State, caller: string) {
            state.requestBody.caller = caller
        },
        addBodyExcludedCallers(state: State, excludedCaller: string) {
            state.requestBody.excluded_callers!.push(excludedCaller)
        },
        deleteBodyExcludedCallers(state: State, excludedCaller: string) {
            state.requestBody.excluded_callers = state.requestBody.excluded_callers!.filter(
                item => item !== excludedCaller
            )
        },
    },
    actions: {
        findProfiling(
            {commit, state}: { commit: any, state: State },
            {traceId}: { traceId: string }
        ) {
            state.loading = true

            state.profiling = {
                nodes: []
            }

            state.parameters = {
                traceId: traceId
            }

            state.requestBody = {
                caller: null,
                excluded_callers: []
            } as Body

            ApiContainer.get().traceAggregatorTracesProfilingCreate(traceId, {})
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
        setProfilingItems({commit}: { commit: any }, profilingItems: Array<ProfilingNode>) {
            commit('setProfilingItems', profilingItems)
        },
        setSelectedProfilingItem({commit}: { commit: any }, item: ProfilingNode | null) {
            commit('setSelectedProfilingItem', item)
        },
        findProfilingWithBody({commit, state}: { commit: any, state: State }) {
            state.loading = true

            state.profiling = {
                nodes: []
            }

            ApiContainer.get()
                .traceAggregatorTracesProfilingCreate(
                    state.parameters.traceId,
                    state.requestBody
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
        setBodyCaller({commit}: { commit: any }, caller: string) {
            commit('setBodyCaller', caller)
        },
        addBodyExcludedCallers({commit}: { commit: any }, excludedCaller: string) {
            commit('addBodyExcludedCallers', excludedCaller)
        },
        deleteBodyExcludedCallers({commit}: { commit: any }, excludedCaller: string) {
            commit('deleteBodyExcludedCallers', excludedCaller)
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
