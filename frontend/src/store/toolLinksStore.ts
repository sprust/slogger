import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";

export type ToolsLink = AdminApi.ToolsLinksList.ResponseBody['data'][number];

interface ToolLinksStoreInterface {
    loaded: boolean
    toolLinks: Array<ToolsLink>
}

export const useToolLinksStore = defineStore('toolLinksStore', {
    state: (): ToolLinksStoreInterface => {
        return {
            loaded: false,
            toolLinks: [] as Array<ToolsLink>
        }
    },
    actions: {
        async findToolLinks() {
            this.loaded = false

            return await ApiContainer.get().toolsLinksList()
                .then(response => {
                    this.toolLinks = response.data.data
                })
                .finally(() => {
                    this.loaded = true
                })
        }
    },
})
