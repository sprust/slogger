import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

export type TraceAggregatorService = AdminApi.ServicesList.ResponseBody['data'][number];

interface State {
    loading: boolean
    items: Array<TraceAggregatorService>
}

export const traceAggregatorServicesStore = createStore<State>({
    state: {
        loading: true,
        items: [] as Array<TraceAggregatorService>
    } as State,
    mutations: {
        setServices(state: State, items: Array<TraceAggregatorService>) {
            state.items = items
        },
    },
    actions: {
        findServices({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().servicesList()
                .then(response => {
                    commit('setServices', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        }
    },
})

export const traceAggregatorServicesStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorServicesStore(): Store<State> {
    return baseUseStore(traceAggregatorServicesStoreInjectionKey)
}
