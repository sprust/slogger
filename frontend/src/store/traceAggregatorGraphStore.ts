import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../utils/apiContainer.ts";
import {convertDateStringToLocal, handleApiError} from "../utils/helpers.ts";
import {ChartData, ChartOptions} from 'chart.js'

type TraceAggregatorTraceMetricsPayload = AdminApi.TraceAggregatorTraceMetricsCreate.RequestBody
type TraceAggregatorTraceMetricResponse = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody
type TraceAggregatorTraceMetricItem = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody['data']['items'][number]

interface State {
    showGraph: boolean,
    loading: boolean,

    payload: TraceAggregatorTraceMetricsPayload,

    loggedAtFrom: string,
    metrics: Array<TraceAggregatorTraceMetricItem>,

    graphData: ChartData,
    graphOptions: ChartOptions,

    preTimestamp: string | null,
    preTimestampCounts: number,
}

export const traceAggregatorGraphStore = createStore<State>({
    state: {
        showGraph: false,
        loading: false,

        payload: {} as TraceAggregatorTraceMetricsPayload,

        loggedAtFrom: '',
        metrics: new Array<TraceAggregatorTraceMetricItem>,

        graphData: {
            labels: [],
            datasets: []
        } as ChartData,
        graphOptions: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                x: {
                    grid: {
                        color: 'rgba(121,146,248,0.1)'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(121,146,248,0.1)'
                    }
                },
            }
        } as ChartOptions,

        preTimestamp: null
    } as State,
    mutations: {
        setMetrics(state: State, data: TraceAggregatorTraceMetricResponse) {
            state.loggedAtFrom = data.data.loggedAtFrom
            state.metrics = data.data.items

            if (!state.metrics.length) {
                state.graphData = {
                    labels: [],
                    datasets: [],
                }

                return
            }

            const timestamp = state.metrics[state.metrics.length - 1].timestamp;

            const totalCount = state.metrics.reduce((a, b) => {
                return a + b.count;
            }, 0)

            if (state.preTimestamp === timestamp
                && totalCount === state.preTimestampCounts
            ) {
                return;
            }

            state.preTimestamp = timestamp
            state.preTimestampCounts = totalCount

            const labels: Array<string> = []

            const datasetCountData: Array<number> = []
            const datasetDurationPercentData: Array<number> = []

            state.metrics.forEach((item: TraceAggregatorTraceMetricItem) => {
                labels.push(
                    convertDateStringToLocal(item.timestamp, false)
                )

                datasetCountData.push(item.count)
                datasetDurationPercentData.push(item.durationPercent)
            })

            state.graphData = {
                labels: labels,
                datasets: [
                    {
                        label: 'count',
                        backgroundColor: 'rgba(163,248,121)',
                        data: datasetCountData
                    },
                    {
                        hidden: true,
                        label: 'duration',
                        backgroundColor: 'rgba(121,146,248,0.3)',
                        data: datasetDurationPercentData
                    },
                ],
            }
        },
    },
    actions: {
        async findMetrics({commit, state}: { commit: any, state: State }) {
            state.loading = true

            try {
                const response = await ApiContainer.get().traceAggregatorTraceMetricsCreate(state.payload)

                commit('setMetrics', response.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
    },
})

export const traceAggregatorGraphStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorGraphStore(): Store<State> {
    return baseUseStore(traceAggregatorGraphStoreInjectionKey)
}
