import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "./traceAggregatorDataStore.ts";
import {handleApiError} from "../../../../utils/helpers.ts";
import {TraceAggregatorService} from "./traceAggregatorServicesStore.ts";

type TraceAggregatorTreeNodeParameters = AdminApi.TraceAggregatorTracesTreeDetail.RequestParams
export type TraceAggregatorTree = AdminApi.TraceAggregatorTracesTreeDetail.ResponseBody['data']
export type TraceAggregatorTreeNode = AdminApi.TraceAggregatorTracesTreeDetail.ResponseBody['data']['items'][number]

interface State {
    loading: boolean,
    parameters: TraceAggregatorTreeNodeParameters,
    tracesCount: number,
    treeNodes: Array<TraceAggregatorTreeNode>,
    dataLoading: boolean,
    selectedTrace: TraceAggregatorDetail,
    traceTypes: Array<string>,
    selectedTraceTypes: Array<string>,
    traceServices: Array<TraceAggregatorService>,
    selectedTraceServiceIds: Array<number>,
    traceTotalIndicatorsNumber: number,
    traceIndicatingIds: Array<string>,
}

const parseTree = function (state: State, treeNodes: Array<TraceAggregatorTreeNode>) {
    state.traceTypes = []
    state.selectedTraceTypes = []
    state.traceServices = []
    state.selectedTraceServiceIds = []

    parseTreeRecursive(state, treeNodes)
}

const parseTreeRecursive = function (state: State, treeNodes: Array<TraceAggregatorTreeNode>) {
    treeNodes.forEach((treeNode: TraceAggregatorTreeNode) => {
        if (treeNode.service?.id
            && !state.traceServices.find(
                (service: TraceAggregatorService) => treeNode.service?.id === service.id
            )
        ) {
            state.traceServices.push(treeNode.service)
        }

        if (state.traceTypes.indexOf(treeNode.type) === -1) {
            state.traceTypes.push(treeNode.type)
        }

        // @ts-ignore
        parseTreeRecursive(state, treeNode.children)
    })
}

export const calcTraceIndicators = function (state: State, treeNodes: Array<TraceAggregatorTreeNode>) {
    state.traceTotalIndicatorsNumber = 0
    state.traceIndicatingIds = []

    calcTraceIndicatorsRecursive(state, treeNodes)
}

const calcTraceIndicatorsRecursive = function (state: State, treeNodes: Array<TraceAggregatorTreeNode>) {
    treeNodes.forEach((treeNode: TraceAggregatorTreeNode) => {
        state.traceIndicatingIds.push(treeNode.trace_id)

        state.traceTotalIndicatorsNumber += (treeNode.duration ?? 0)

        // @ts-ignore
        calcTraceIndicatorsRecursive(state, treeNode.children)
    })
}

export const traceAggregatorTreeStore = createStore<State>({
    state: {
        loading: false,
        parameters: {} as TraceAggregatorTreeNodeParameters,
        tracesCount: 0,
        treeNodes: new Array<TraceAggregatorTreeNode>,
        dataLoading: false,
        selectedTrace: {} as TraceAggregatorDetail,
        traceTypes: new Array<string>(),
        selectedTraceTypes: new Array<string>(),
        traceServices: new Array<TraceAggregatorService>(),
        selectedTraceServiceIds: new Array<number>(),
        traceTotalIndicatorsNumber: 0,
        traceIndicatingIds: []
    } as State,
    mutations: {
        setTreeNodes(state: State, tree: TraceAggregatorTree) {
            state.tracesCount = tree.tracesCount
            state.treeNodes = tree.items

            parseTree(state, state.treeNodes)
            calcTraceIndicators(state, state.treeNodes)
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

                commit('setTreeNodes', response.data.data)
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

                commit('setTreeNodes', response.data.data)
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
