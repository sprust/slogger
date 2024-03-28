import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {convertDateStringToLocal, handleApiError} from "../utils/helpers.ts";

type DashboardServiceStatItem = AdminApi.DashboardServiceStatList.ResponseBody['data'][number];

interface State {
    loading: boolean
    periods: Array<string>,
    services: Array<Service>
}

export interface Service {
    id: number
    name: string
    periods: Periods
}

interface Periods {
    [key: string]: Period,
}

interface Period {
    types: Array<Type>
}

export interface Type {
    name: string
    statuses: Array<Status>
}

interface Status {
    name: string
    count: number
}

export const dashboardServiceStatStore = createStore<State>({
    state: {
        loading: true,
        periods: new Array<string>(),
        services: new Array<Service>()
    } as State,
    mutations: {
        fillServiceStatItems(state: State, items: Array<DashboardServiceStatItem>) {
            state.periods = []
            state.services = []

            items.map((item: DashboardServiceStatItem) => {
                const periodKey =
                    convertDateStringToLocal(item.to, false)
                    + ' - '
                    + convertDateStringToLocal(item.from, false)

                if (state.periods.indexOf(periodKey) === -1) {
                    state.periods.push(periodKey)
                }

                const serviceId = item.service.id
                const serviceName = item.service.name
                const typeName = item.type
                const statusName = item.status
                const count = item.count

                let service: Service | undefined = state.services.find((serviceItem: Service) => serviceItem.id === serviceId)

                if (!service) {
                    service = {
                        id: serviceId,
                        name: serviceName,
                        periods: {}
                    }

                    state.services.push(service)
                }

                let period: Period | undefined = service.periods[periodKey]

                if (!period) {
                    period = {
                        types: []
                    }

                    service.periods[periodKey] = period
                }

                let type: Type | undefined = period.types.find((typeItem: Type) => typeItem.name === typeName)

                if (!type) {
                    type = {
                        name: typeName,
                        statuses: []
                    }

                    period.types.push(type)
                }

                type.statuses.push({
                    name: statusName,
                    count: count
                })
            })
        },
    },
    actions: {
        findDashboardServiceStat({commit, state}: { commit: any, state: State }) {
            state.loading = true

            ApiContainer.get().dashboardServiceStatList()
                .then(response => {
                    commit('fillServiceStatItems', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        }
    },
})

export const dashboardServiceStatStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useDashboardServiceStatStore(): Store<State> {
    return baseUseStore(dashboardServiceStatStoreInjectionKey)
}
