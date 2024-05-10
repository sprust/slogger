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
    treeFilter: string,
    selectedItemId: string,
    selectedItem: ProfilingItem | null,
    flowItems: FlowItems
}

export const traceAggregatorProfilingStore = createStore<State>({
    state: {
        loading: false,
        parameters: {} as Parameters,
        profilingItems: new Array<ProfilingItem>,
        treeFilter: '',
        selectedItemId: '',
        selectedItem: null as ProfilingItem | null,
        flowItems: {
            nodes: [],
            edges: [],
        }
    } as State,
    mutations: {
        setProfilingItems(state: State, profilingItems: Array<ProfilingItem>) {
            state.profilingItems = profilingItems
        },
        setSelectedProfilingItem(state: State, item: ProfilingItem | null) {
            state.selectedItem = item
        },
    },
    actions: {
        findProfiling(
            {commit, state}: { commit: any, state: State },
            parameters: Parameters
        ) {
            state.loading = true

            commit('resetData')

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
    },
})

export const traceAggregatorProfilingStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorProfilingStore(): Store<State> {
    return baseUseStore(traceAggregatorProfilingStoreInjectionKey)
}
