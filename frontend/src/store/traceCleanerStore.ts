import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type TraceCleanerSettingItem = AdminApi.TraceCleanerSettingsList.ResponseBody['data'][number];
export type TraceCleanerSettingProcessItem = AdminApi.TraceCleanerSettingsProcessesDetail.ResponseBody['data'][number];

interface Processes {
    [key: string]: Array<TraceCleanerSettingProcessItem>;
}

interface State {
    loading: boolean
    settings: Array<TraceCleanerSettingItem>
    processes: Processes
}

export const traceCleanerStore = createStore<State>({
    state: {
        loading: true,
        settings: [] as Array<TraceCleanerSettingItem>,
        processes: {} as Processes
    } as State,
    mutations: {
        setTraceCleanerSettings(state: State, items: Array<TraceCleanerSettingItem>) {
            state.settings = items
            state.processes = {}
        },
        setTraceCleanerSettingProcesses(
            state: State,
            {settingId, processes}: { settingId: number, processes: Array<TraceCleanerSettingProcessItem> }
        ) {
            state.processes[settingId] = processes
        },
        editSetting(state: State, setting: TraceCleanerSettingItem) {
            state.settings = state.settings.map((settingItem: TraceCleanerSettingItem) => {
                return settingItem.id === setting.id ? setting : settingItem
            })
        },
        deleteSetting(state: State, settingId: number) {
            state.settings.map((settingItem: TraceCleanerSettingItem) => {
                if (settingItem.id !== settingId) {
                    return
                }

                settingItem.deleted = true
            })
        },
    },
    actions: {
        findTraceCleanerSettings({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().traceCleanerSettingsList()
                .then(response => {
                    commit('setTraceCleanerSettings', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
        findTraceCleanerProcesses({commit}: { commit: any }, settingId: number) {
            ApiContainer.get().traceCleanerSettingsProcessesDetail(settingId)
                .then(response => {
                    commit(
                        'setTraceCleanerSettingProcesses',
                        {
                            settingId,
                            processes: response.data.data
                        }
                    )
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        async createSetting(
            {},
            {daysLifetime, type, onSuccess}: {
                daysLifetime: number,
                type: string
                onSuccess: () => {}
            }
        ) {
            ApiContainer.get()
                .traceCleanerSettingsCreate({
                    days_life_time: daysLifetime,
                    type: type
                })
                .then(() => {
                    onSuccess()
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        updateSetting(
            {commit}: { commit: any },
            {settingId, daysLifetime, onSuccess}: {
                settingId: number | null,
                daysLifetime: number,
                onSuccess: () => {}
            }
        ) {
            ApiContainer.get()
                .traceCleanerSettingsPartialUpdate(settingId, {
                    days_life_time: daysLifetime,
                })
                .then((response) => {
                    commit('editSetting', response.data.data)

                    onSuccess()
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        async deleteSetting(
            {commit}: { commit: any },
            settingId: number
        ) {
            try {
                await ApiContainer.get().traceCleanerSettingsDelete(settingId);

                commit('deleteSetting', settingId)
            } catch (error) {
                handleApiError(error)
            }
        },
    },
})

export const traceCleanerStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceCleanerStore(): Store<State> {
    return baseUseStore(traceCleanerStoreInjectionKey)
}
