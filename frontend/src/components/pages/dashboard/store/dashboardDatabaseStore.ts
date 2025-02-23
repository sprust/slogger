import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type DashboardDatabaseItem = AdminApi.DashboardDatabaseList.ResponseBody['data'][number];

interface DashboardDatabaseStoreInterface {
    loading: boolean
    items: Array<DashboardDatabaseItem>
}

export const useDashboardDatabaseStore = defineStore('dashboardDatabase', {
    state: (): DashboardDatabaseStoreInterface => {
        return {
            loading: true,
            items: []
        }
    },
    actions: {
        async findDashboardDatabase() {
            this.loading = true

            return await handleApiRequest(
                ApiContainer.get().dashboardDatabaseList()
                    .then(response => {
                        this.items = response.data.data

                        return response
                    })
                    .catch((error) => {
                        handleApiError(error)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        }
    },
})
