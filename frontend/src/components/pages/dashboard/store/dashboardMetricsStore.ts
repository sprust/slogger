import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {formatUtcDateTime} from "../../../../utils/helpers.ts";

export type DashboardTraceMetric = AdminApi.DashboardTraceMetricsDetail.ResponseBody['data'][number];

export const metricSlotMinutes = 15

export const metricSlotsCount = 96

export const initialChartsCount = 3

export interface DashboardServiceMetrics {
    loading: boolean,
    loaded: boolean,
    slots: Array<string>,
    rows: Array<DashboardTraceMetric>,
    selectedTypes: Array<string>,
}

interface DashboardMetricsStoreInterface {
    initialized: boolean,
    selectedServiceIds: Array<number>,
    charts: Record<number, DashboardServiceMetrics>,
}

function makeSlots(now: number): Array<string> {
    const slotMs = metricSlotMinutes * 60 * 1000
    const current = Math.floor(now / slotMs) * slotMs

    return Array.from(
        {length: metricSlotsCount},
        (_, index) => formatUtcDateTime(new Date(current - (metricSlotsCount - 1 - index) * slotMs))
    )
}

function emptyChart(): DashboardServiceMetrics {
    return {
        loading: false,
        loaded: false,
        slots: [],
        rows: [],
        selectedTypes: [],
    }
}

export const useDashboardMetricsStore = defineStore('dashboardMetricsStore', {
    state: (): DashboardMetricsStoreInterface => {
        return {
            initialized: false,
            selectedServiceIds: [],
            charts: {},
        }
    },
    getters: {
        busy(state: DashboardMetricsStoreInterface): boolean {
            return Object.values(state.charts).some(chart => chart.loading)
        },
    },
    actions: {
        chart(serviceId: number): DashboardServiceMetrics {
            if (!this.charts[serviceId]) {
                this.charts[serviceId] = emptyChart()
            }

            return this.charts[serviceId]
        },
        async findServiceMetrics(serviceId: number) {
            const chart = this.chart(serviceId)

            chart.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().dashboardTraceMetricsDetail(serviceId)
                    .then(response => {
                        chart.rows = response.data.data
                        chart.slots = makeSlots(Date.now())
                        chart.loaded = true
                    })
                    .finally(() => {
                        chart.loading = false
                    })
            )
        },
        async loadSequentially(serviceIds: Array<number>) {
            for (const serviceId of serviceIds) {
                await this.findServiceMetrics(serviceId)
            }
        },
        async refreshLoaded(serviceIds: Array<number>) {
            return this.loadSequentially(
                serviceIds.filter(serviceId => this.charts[serviceId]?.loaded)
            )
        },
    },
})
