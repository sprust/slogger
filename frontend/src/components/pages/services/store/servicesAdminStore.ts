import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {useTraceAggregatorServicesStore} from "../../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

export type ServiceDetail = AdminApi.ServicesDetail.ResponseBody['data'];

interface ServicesAdminStoreInterface {
    details: Record<number, ServiceDetail>
}

export const useServicesAdminStore = defineStore('servicesAdminStore', {
    state: (): ServicesAdminStoreInterface => {
        return {
            details: {},
        }
    },
    actions: {
        async findDetail(id: number) {
            return await handleApiRequest(
                () => ApiContainer.get().servicesDetail(id)
                    .then(response => {
                        this.details[id] = response.data.data
                    })
            )
        },
        async create(name: string): Promise<ServiceDetail | null> {
            return await handleApiRequest(
                () => ApiContainer.get().servicesCreate({name: name})
                    .then(async response => {
                        this.details[response.data.data.id] = response.data.data

                        await useTraceAggregatorServicesStore().findServices()

                        return response.data.data
                    })
            ) ?? null
        },
        async rename(id: number, name: string): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().servicesPartialUpdate(id, {name: name})
                    .then(async response => {
                        this.details[id] = response.data.data

                        await useTraceAggregatorServicesStore().findServices()

                        return true
                    })
            ) === true
        },
        async sort(ids: Array<number>) {
            return await handleApiRequest(
                () => ApiContainer.get().servicesPositionsPartialUpdate({ids: ids})
            ).finally(() => useTraceAggregatorServicesStore().findServices())
        },
    },
})
