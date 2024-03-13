import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type DashboardDatabaseItem = AdminApi.DashboardDatabaseList.ResponseBody['data'][number];

interface State {
    loading: boolean
    items: Array<DashboardDatabaseItem>
}

export const dashboardDatabaseStore = createStore<State>({
    state: {
        loading: true,
        items: [] as Array<DashboardDatabaseItem>
    } as State,
    mutations: {
        setDatabaseItems(state: State, items: Array<DashboardDatabaseItem>) {
            state.items = items
        },
    },
    actions: {
        findDashboardDatabase({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().dashboardDatabaseList()
                .then(response => {
                    commit('setDatabaseItems', response.data.data)
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

export const dashboardDatabaseStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useDashboardDatabaseStore(): Store<State> {
    return baseUseStore(dashboardDatabaseStoreInjectionKey)
}
