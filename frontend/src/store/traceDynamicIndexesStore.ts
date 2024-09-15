import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type TraceDynamicIndex = AdminApi.TraceAggregatorDynamicIndexesList.ResponseBody['data'][number];

interface State {
    loaded: boolean
    loading: boolean
    traceDynamicIndexes: Array<TraceDynamicIndex>
}

export const traceDynamicIndexesStore = createStore<State>({
    state: {
        loaded: false,
        loading: true,
        traceDynamicIndexes: [] as Array<TraceDynamicIndex>
    } as State,
    mutations: {
        setTraceDynamicIndexes(state: State, indexes: Array<TraceDynamicIndex>) {
            state.traceDynamicIndexes = indexes
        },
        deleteTraceDynamicIndexes(state: State, id: string) {
            state.traceDynamicIndexes = state.traceDynamicIndexes.filter(
                (index: TraceDynamicIndex) => index.id !== id
            )
        },
    },
    actions: {
        findTraceDynamicIndexes({commit, state}: { commit: any, state: State }) {
            state.loaded = true

            state.loading = true

            return ApiContainer.get().traceAggregatorDynamicIndexesList()
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
        deleteTraceDynamicIndex(
            {commit, state}: { commit: any, state: State },
            {id}: {id: string}
        ) {
            state.loading = true

            return ApiContainer.get().traceAggregatorDynamicIndexesDelete(id)
                .then(() => {
                    commit('deleteTraceDynamicIndexes', id)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
    },
})

export const traceDynamicIndexesInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceDynamicIndexesStore(): Store<State> {
    return baseUseStore(traceDynamicIndexesInjectionKey)
}
