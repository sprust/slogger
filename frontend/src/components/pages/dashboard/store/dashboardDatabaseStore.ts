import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";
import {defineStore} from "pinia";

export type DashboardDatabaseItem = AdminApi.DashboardDatabaseList.ResponseBody['data'][number];

interface DashboardDatabaseStateInterface {
    loading: boolean
    items: Array<DashboardDatabaseItem>
}

export const useDashboardDatabaseStore = defineStore('dashboardDatabase', {
    state: (): DashboardDatabaseStateInterface => {
        return {
            loading: true,
            items: []
        }
    },
    actions: {
        async findDashboardDatabase() {
            this.loading = true

            return ApiContainer.get().dashboardDatabaseList()
                .then(response => {
                    this.items = response.data.data
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    this.loading = false
                })
        }
    },
})
