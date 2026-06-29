import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type SconcurStat = AdminApi.DashboardSconcurList.ResponseBody['data'];

interface DashboardSconcurStoreInterface {
    loading: boolean
    stat: SconcurStat | null
}

export const useDashboardSconcurStore = defineStore('dashboardSconcur', {
    state: (): DashboardSconcurStoreInterface => {
        return {
            loading: false,
            stat: null,
        }
    },
    actions: {
        async findSconcurStat() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().dashboardSconcurList()
                    .then(response => {
                        this.stat = response.data.data

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        }
    },
})
