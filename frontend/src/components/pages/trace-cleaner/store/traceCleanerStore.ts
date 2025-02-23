import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type TraceCleanerSettingItem = AdminApi.TraceCleanerSettingsList.ResponseBody['data'][number];
export type TraceCleanerSettingProcessItem = AdminApi.TraceCleanerSettingsProcessesDetail.ResponseBody['data'][number];

interface SettingProcesses {
    [key: string]: Array<TraceCleanerSettingProcessItem>;
}

interface TraceCleanerStoreInterface {
    loading: boolean
    settings: Array<TraceCleanerSettingItem>
    settingProcesses: SettingProcesses
}

export const useTraceCleanerStore = defineStore('traceCleanerStore', {
    state: (): TraceCleanerStoreInterface => {
        return {
            loading: true,
            settings: [] as Array<TraceCleanerSettingItem>,
            settingProcesses: {} as SettingProcesses
        }
    },
    actions: {
        async findTraceCleanerSettings() {
            this.loading = true

            return await handleApiRequest(
                ApiContainer.get().traceCleanerSettingsList()
                    .then(response => {
                        this.settings = response.data.data
                        this.settingProcesses = {}

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findTraceCleanerProcesses(settingId: number) {
            return await handleApiRequest(
                ApiContainer.get().traceCleanerSettingsProcessesDetail(settingId)
                    .then((response) => {
                        this.settingProcesses[settingId] = response.data.data
                    })
            )
        },
        async createSetting(
            daysLifetime: number,
            type: string,
            onlyData: boolean
        ) {
            return await handleApiRequest(
                ApiContainer.get()
                    .traceCleanerSettingsCreate({
                        days_life_time: daysLifetime,
                        type: type,
                        only_data: onlyData
                    })
            )
        },
        async updateSetting(
            settingId: number | null,
            daysLifetime: number,
            onlyData: boolean,
        ) {
            return await handleApiRequest(
                ApiContainer.get()
                    .traceCleanerSettingsPartialUpdate(settingId, {
                        days_life_time: daysLifetime,
                        only_data: onlyData
                    })
                    .then((response) => {
                        const setting = response.data.data

                        this.settings = this.settings.map((settingItem: TraceCleanerSettingItem) => {
                            return settingItem.id === setting.id ? setting : settingItem
                        })
                    })
            )
        },
        async deleteSetting(settingId: number) {
            return await handleApiRequest(
                ApiContainer.get().traceCleanerSettingsDelete(settingId)
                    .then(() => {
                        this.settings.map((settingItem: TraceCleanerSettingItem) => {
                            if (settingItem.id !== settingId) {
                                return
                            }

                            settingItem.deleted = true
                        })
                    })
            )
        },
    },
})
