import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../../../utils/helpers.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type AdminStores = AdminApi.TraceAggregatorStatesList.ResponseBody['data']
export type AdminStore = AdminApi.TraceAggregatorStatesList.ResponseBody['data']['items'][number]
export type AdminStoresParameters = AdminApi.TraceAggregatorStatesList.RequestQuery
export type AdminStoreCreateParameters = AdminApi.TraceAggregatorStatesCreate.RequestBody

interface DeletedIds {
    [key: string]: boolean
}

interface AdminStoresStoreInterface {
    loading: boolean
    findParameters: AdminStoresParameters
    adminStores: AdminStores
    createParameters: AdminStoreCreateParameters
    deletedIds: DeletedIds
}

export const useTraceAdminStoresStore = defineStore('traceAdminStoresStore', {
    state: (): AdminStoresStoreInterface => {
        return {
            loading: true,
            findParameters: {
                page: 1,
                version: 0,
                search_query: '',
                auto: false
            },
            adminStores: {
                items: [],
                paginator: {
                    total: 0,
                    total_pages: 0,
                    per_page: 0,
                    current_page: 1
                }
            },
            createParameters: {
                title: '',
                store_version: 0,
                store_data: '',
                auto: false
            },
            deletedIds: {}
        }
    },
    actions: {
        findAdminStores() {
            this.loading = true

            ApiContainer.get().traceAggregatorStatesList(this.findParameters)
                .then(response => {
                    this.deletedIds = {}
                    this.adminStores = response.data.data
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    this.loading = false
                })
        },
        clearAdminStoreCreateParameters() {
            this.createParameters = {
                title: '',
                store_version: 0,
                store_data: '',
                auto: false
            }
        },
        async createAdminStore() {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorStatesCreate(this.createParameters)
            )
        },
        async deleteAdminStore(id: string) {
            return await ApiContainer.get().traceAggregatorStatesDelete(id)
                .then(() => {
                    this.deletedIds[id] = true

                    this.loading = false
                })
        }
    },
})
