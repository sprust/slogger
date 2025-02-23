import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

export type TraceDynamicIndex = AdminApi.TraceAggregatorDynamicIndexesList.ResponseBody['data'][number];
export type TraceDynamicIndexStats = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']
export type TraceDynamicIndexInfo = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']['indexes_in_process'][number]

interface State {
    started: boolean,
    loading: boolean
    traceDynamicIndexStats: TraceDynamicIndexStats,
    traceDynamicIndexes: Array<TraceDynamicIndex>
}

export const traceDynamicIndexesStore = createStore<State>({
    state: {
        started: false,
        loading: false,
        traceDynamicIndexStats: {} as TraceDynamicIndexStats,
        traceDynamicIndexes: [] as Array<TraceDynamicIndex>
    } as State,
    mutations: {
        setTraceDynamicIndexes(state: State, indexes: Array<TraceDynamicIndex>) {
            state.traceDynamicIndexes = indexes
        },
        setTraceDynamicIndexStats(state: State, stats: TraceDynamicIndexStats) {
            state.traceDynamicIndexStats = stats
        },
        deleteTraceDynamicIndexes(state: State, id: string) {
            state.traceDynamicIndexes = state.traceDynamicIndexes.filter(
                (index: TraceDynamicIndex) => index.id !== id
            )
        },
    },
    actions: {
        findTraceDynamicIndexes({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().traceAggregatorDynamicIndexesList()
                .then(response => {
                    commit('setTraceDynamicIndexes', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
        findTraceDynamicIndexStats({commit, state}: { commit: any, state: State }) {
            state.started = true

            return ApiContainer.get().traceAggregatorDynamicIndexesStatsList()
                .then(response => {
                    commit('setTraceDynamicIndexStats', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        deleteTraceDynamicIndex(
            {commit}: { commit: any },
            {id}: {id: string}
        ) {
            return ApiContainer.get().traceAggregatorDynamicIndexesDelete(id)
                .then(() => {
                    commit('deleteTraceDynamicIndexes', id)
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
    },
})

export const traceDynamicIndexesInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceDynamicIndexesStore(): Store<State> {
    return baseUseStore(traceDynamicIndexesInjectionKey)
}
