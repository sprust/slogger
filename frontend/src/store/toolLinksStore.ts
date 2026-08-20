import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../utils/handleApiRequest.ts";

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

            await handleApiRequest(async () => {
                const response = await ApiContainer.get().toolsLinksList()

                this.toolLinks = response.data.data
            })

            this.loaded = true
        }
    },
})
