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

type Parameters = AdminApi.TraceAggregatorTracesProfilingDetail.RequestParams
export type ProfilingItem = AdminApi.TraceAggregatorTracesProfilingDetail.ResponseBody['data'][number]

export interface FlowItems {
    nodes: Array<Node>,
    edges: Array<Edge>,
}

interface State {
    loading: boolean,
    parameters: Parameters,
    profilingItems: Array<ProfilingItem>,
    selectedItem: ProfilingItem | null,
    profilingTreeFilter: string,
    profilingTree: Array<ProfilingTreeNode>,
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
        loading: false,
        parameters: {} as Parameters,
        profilingItems: new Array<ProfilingItem>,
        selectedItem: null as ProfilingItem | null,
        profilingTreeFilter: '',
        profilingTree: [] as Array<ProfilingTreeNode>,
        flowItems: {
            nodes: [],
            edges: [],
        },
    } as State,
    mutations: {
        setProfilingItems(state: State, profilingItems: Array<ProfilingItem>) {
            state.profilingItems = profilingItems

            state.profilingTree = (new ProfilingTreeBuilder()).build(state.profilingItems)
        },
        buildProfilingTree(state: State, item: ProfilingItem | null) {
            if (!item) {
                state.profilingTree = []

                return
            }

            state.profilingTree = (new ProfilingTreeBuilder()).build([item])
        },
        setSelectedProfilingItem(state: State, item: ProfilingItem | null) {
            if (!item) {
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
        setSelectedProfilingItem({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('setSelectedProfilingItem', item)
        },
        buildProfilingTree({commit}: { commit: any }, item: ProfilingItem | null) {
            commit('buildProfilingTree', item)
        },
    },
})

export const traceAggregatorProfilingStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorProfilingStore(): Store<State> {
    return baseUseStore(traceAggregatorProfilingStoreInjectionKey)
}
