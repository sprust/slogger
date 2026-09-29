import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../utils/handleApiRequest.ts";

export type McpSettings = AdminApi.McpsSettingsList.ResponseBody['data'];

interface McpSettingsStoreInterface {
    loaded: boolean
    settings: McpSettings | null
}

let pending: Promise<void> | null = null

export const useMcpSettingsStore = defineStore('mcpSettingsStore', {
    state: (): McpSettingsStoreInterface => {
        return {
            loaded: false,
            settings: null,
        }
    },
    actions: {
        async find() {
            pending ??= handleApiRequest(async () => {
                const response = await ApiContainer.get().mcpsSettingsList()

                this.settings = response.data.data
            }).then(() => {
                this.loaded = true
            }).finally(() => {
                pending = null
            })

            return pending
        },
    },
})
