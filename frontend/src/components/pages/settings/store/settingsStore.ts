import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type Setting = AdminApi.SettingsList.ResponseBody['data'][number];

interface SettingsStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<Setting>
}

export const useSettingsStore = defineStore('settingsStore', {
    state: (): SettingsStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [],
        }
    },
    actions: {
        async findSettings() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().settingsList()
                    .then(response => {
                        this.items = response.data.data
                        this.loaded = true
                    })
            ).finally(() => {
                this.loading = false
            })
        },
        async update(key: string, value: number): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().settingsPartialUpdate(key, {value: value})
                    .then(async () => {
                        await this.findSettings()

                        return true
                    })
            ) === true
        },
        async reset(key: string): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().settingsDelete(key)
                    .then(async () => {
                        await this.findSettings()

                        return true
                    })
            ) === true
        },
    },
})
