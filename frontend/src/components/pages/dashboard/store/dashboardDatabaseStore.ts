import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type DashboardDatabaseItem = AdminApi.DashboardDatabaseList.ResponseBody['data']['items'][number];

interface DashboardDatabaseStoreInterface {
    loading: boolean
    cachedAt: string | null
    items: Array<DashboardDatabaseItem>
}

export const useDashboardDatabaseStore = defineStore('dashboardDatabase', {
    state: (): DashboardDatabaseStoreInterface => {
        return {
            loading: true,
            cachedAt: null,
            items: []
        }
    },
    actions: {
        async findDashboardDatabase() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().dashboardDatabaseList()
                    .then(response => {
                        this.cachedAt = response.data.data.cached_at
                        this.items = response.data.data.items

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        }
    },
})
