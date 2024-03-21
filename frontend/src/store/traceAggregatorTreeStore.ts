import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "./traceAggregatorDataStore.ts";
import {handleApiError} from "../utils/helpers.ts";

type TraceAggregatorTreeNodeParameters = AdminApi.TraceAggregatorTracesTreeDetail.RequestParams
export type TraceAggregatorTreeNode = AdminApi.TraceAggregatorTracesTreeDetail.ResponseBody['data']['items'][number]

interface State {
    loading: boolean,
    parameters: TraceAggregatorTreeNodeParameters,
    treeNodes: Array<TraceAggregatorTreeNode>,
    dataLoading: boolean,
    selectedTrace: TraceAggregatorDetail
}

export const traceAggregatorTreeStore = createStore<State>({
    state: {
        loading: false,
        parameters: {} as TraceAggregatorTreeNodeParameters,
        treeNodes: new Array<TraceAggregatorTreeNode>,
        dataLoading: false,
        selectedTrace: {} as TraceAggregatorDetail
    } as State,
    mutations: {
        setTreeNodes(state: State, treeNodes: Array<TraceAggregatorTreeNode>) {
            state.treeNodes = treeNodes
        },
        setData(state: State, trace: TraceAggregatorDetail) {
            state.selectedTrace = trace
        },
        resetData(state: State) {
            state.selectedTrace = {} as TraceAggregatorDetail
        }
    },
    actions: {
        async findTreeNodes(
            {commit, state}: { commit: any, state: State },
            parameters: TraceAggregatorTreeNodeParameters
        ) {
            state.loading = true

            commit('resetData')

            state.treeNodes = []

            state.parameters = parameters

            try {
                const response = await ApiContainer.get().traceAggregatorTracesTreeDetail(parameters.traceId)

                commit('setTreeNodes', response.data.data.items)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
        async refreshTree({commit, state}: { commit: any, state: State }) {
            state.loading = true

            commit('resetData')

            state.treeNodes = []

            try {
                const response = await ApiContainer.get().traceAggregatorTracesTreeDetail(state.parameters.traceId)

                commit('setTreeNodes', response.data.data.items)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
        findData({commit, state}: { commit: any, state: State }, traceId: string) {
            if (traceId === state.selectedTrace.trace_id) {
                commit('resetData')

                return
            }

            state.dataLoading = true

            ApiContainer.get().traceAggregatorTracesDetail(traceId)
                .then(response => {
                    commit('setData', response.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.dataLoading = false
                })
        },
        resetData({commit}: { commit: any }) {
            commit('resetData')
        }
    },
})

export const traceAggregatorTreeStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTreeStore(): Store<State> {
    return baseUseStore(traceAggregatorTreeStoreInjectionKey)
}
