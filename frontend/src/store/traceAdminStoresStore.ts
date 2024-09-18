import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type AdminStores = AdminApi.TraceAggregatorStatesList.ResponseBody['data']
export type AdminStore = AdminApi.TraceAggregatorStatesList.ResponseBody['data']['items'][number]
export type AdminStoresParameters = AdminApi.TraceAggregatorStatesList.RequestQuery
export type AdminStoreCreateParameters = AdminApi.TraceAggregatorStatesCreate.RequestBody

interface DeletedIds {
    [key: string]: boolean
}

interface State {
    loading: boolean
    adminStoresParameters: AdminStoresParameters
    adminStores: AdminStores
    adminStoreCreateParameters: AdminStoreCreateParameters
    adminStoreDeletedIds: DeletedIds
}

export const traceAdminStoresStore = createStore<State>({
    state: {
        loading: true,
        adminStoresParameters: {
            page: 1,
            version: 0,
            search_query: ''
        },
        adminStores: {
            items: [],
            paginator: {
                total: 0,
                total_pages: 0,
                per_page: 0,
                current_page: 1
            }
        },
        adminStoreCreateParameters: {
            title: '',
            store_version: 0,
            store_data: ''
        },
        adminStoreDeletedIds: {}
    } as State,
    mutations: {
        setAdminStores(state: State, stores: AdminStores) {
            state.adminStoreDeletedIds = {}
            state.adminStores = stores
        },
        clearAdminStoreCreateParameters(state: State) {
            state.adminStoreCreateParameters = {
                title: '',
                store_version: 0,
                store_data: ''
            }
        }
    },
    actions: {
        findAdminStores({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().traceAggregatorStatesList(state.adminStoresParameters)
                .then(response => {
                    commit('setAdminStores', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
        clearAdminStoreCreateParameters({commit}: { commit: any }) {
            commit('clearAdminStoreCreateParameters')
        },
        createAdminStore({state}: { state: State }) {
            return ApiContainer.get().traceAggregatorStatesCreate(state.adminStoreCreateParameters)
                .catch((error) => {
                    handleApiError(error)
                })
        },
        deleteAdminStore({commit, state}: { commit: any, state: State }, id: string) {
            return ApiContainer.get().traceAggregatorStatesDelete(id)
                .then(() => {
                    state.adminStoreDeletedIds[id] = true

                    state.loading = false
                })
                .catch((error) => {
                    handleApiError(error)
                })
        }
    },
})

export const traceAdminStoresStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAdminStoresStoreStore(): Store<State> {
    return baseUseStore(traceAdminStoresStoreInjectionKey)
}
