import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {currentSession, sessionEnded} from "../../../../store/session.ts";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type Mcp = AdminApi.McpsList.ResponseBody['data'][number];

interface McpsStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<Mcp>
}

export const useMcpsStore = defineStore('mcpsStore', {
    state: (): McpsStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<Mcp>,
        }
    },
    actions: {
        async find() {
            this.loading = true

            const session = currentSession()

            return await handleApiRequest(
                () => ApiContainer.get().mcpsList()
                    .then(response => {
                        if (sessionEnded(session)) {
                            return
                        }

                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async create(name: string): Promise<Mcp | null> {
            return await handleApiRequest(
                () => ApiContainer.get().mcpsCreate({name: name})
                    .then(response => {
                        this.items = [...this.items, response.data.data]

                        return response.data.data
                    })
            ) ?? null
        },
        async update(id: number, name: string, enabled: boolean): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().mcpsPartialUpdate(id, {name: name, enabled: enabled})
                    .then(response => {
                        this.replace(response.data.data)

                        return true
                    })
            ) === true
        },
        async regenerateToken(id: number): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().mcpsTokenPartialUpdate(id)
                    .then(response => {
                        this.replace(response.data.data)

                        return true
                    })
            ) === true
        },
        async remove(id: number) {
            return await handleApiRequest(
                () => ApiContainer.get().mcpsDelete(id)
                    .then(() => {
                        this.items = this.items.filter((mcp: Mcp) => mcp.id !== id)
                    })
            )
        },
        replace(mcp: Mcp) {
            this.items = this.items.map((item: Mcp) => item.id === mcp.id ? mcp : item)
        },
    },
})
