import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

type LogsPagination = AdminApi.LogsList.ResponseBody['data']
type LogsParameters = AdminApi.LogsList.RequestQuery

type Log = AdminApi.LogsList.ResponseBody['data']['items'][number]

interface State {
    loading: boolean,
    logsParameters: LogsParameters
    logs: LogsPagination
}

export const logsViewerStore = createStore<State>({
    state: {
        loading: true,
        logsParameters: {
            page: 1,
            search_query: null,
            level: null,
        },
        logs: {
            items: new Array<Log>(),
            paginator: {
                total: 0,
                per_page: 0,
                current_page: 1,
                total_pages: 0
            }
        }
    } as State,
    mutations: {
        setLogsPagination(state: State, logs: LogsPagination) {
            state.logs = logs
        }
    },
    actions: {
        findLogs({state, commit}: { state: State, commit: any }) {
            state.loading = true

            const parameters: LogsParameters = {
                page: state.logsParameters.page
            }

            if (state.logsParameters.search_query) {
                parameters.search_query = state.logsParameters.search_query
            }

            if (state.logsParameters.level) {
                parameters.level = state.logsParameters.level
            }

            ApiContainer.get()
                .logsList(parameters)
                .then((response) => {
                    commit('setLogsPagination', response.data.data)
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

export const logsViewerStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useLogsViewerStoreStore() {
    return baseUseStore(logsViewerStoreInjectionKey)
}
