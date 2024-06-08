import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../utils/apiContainer.ts";
import {convertDateStringToLocal, handleApiError} from "../utils/helpers.ts";
import {ChartData, ChartDataset, ChartOptions} from 'chart.js'

type TraceAggregatorTraceMetricsPayload = AdminApi.TraceAggregatorTraceMetricsCreate.RequestBody
type TraceAggregatorTraceMetricResponse = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody
type TraceAggregatorTraceMetricItem = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody['data']['items'][number]

interface GraphItem {
    name: string,
    data: ChartData
}

interface State {
    showGraph: boolean,
    loading: boolean,
    waiting: boolean,

    payload: TraceAggregatorTraceMetricsPayload,

    loggedAtFrom: string,
    metrics: Array<TraceAggregatorTraceMetricItem>,

    graphs: Array<GraphItem>,
    graphOptions: ChartOptions,

    preTimestamp: string | null,
    preTimestampCounts: number,
}

interface AggregationColors {
    [key: string]: string
}

const defaultAggregationColor: string = 'rgba(248,189,121)'

const graphAggregationColors: AggregationColors = {
    sum: 'rgba(163,248,121)',
    avg: 'rgb(246,188,2)',
    min: 'rgb(0,48,255)',
    max: 'rgb(246,2,2)',
}

export const traceAggregatorGraphStore = createStore<State>({
    state: {
        showGraph: false,
        loading: false,

        payload: {} as TraceAggregatorTraceMetricsPayload,

        loggedAtFrom: '',
        metrics: new Array<TraceAggregatorTraceMetricItem>,

        graphs: new Array<GraphItem>,
        graphOptions: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                x: {
                    grid: {
                        color: 'rgba(121,146,248,0.3)'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(121,146,248,0.2)'
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
                state.graphs = []

                return
            }

            const labels: Array<string> = []

            const fieldIndicators: {
                [key: string]: {
                    [key: string]: Array<number>
                }
            } = {}

            state.metrics.forEach((item: TraceAggregatorTraceMetricItem) => {
                labels.push(
                    convertDateStringToLocal(item.timestamp, false)
                )

                item.fields.forEach(field => {
                    if (fieldIndicators[field.field] === undefined) {
                        fieldIndicators[field.field] = {}
                    }

                    field.indicators.forEach(indicator => {
                        if (fieldIndicators[field.field][indicator.name] === undefined) {
                            fieldIndicators[field.field][indicator.name] = []
                        }

                        fieldIndicators[field.field][indicator.name].push(indicator.value)
                    })
                })
            })

            const graphs: Array<GraphItem> = []

            Object.keys(fieldIndicators).map(fieldIndicatorName => {
                const indicators = fieldIndicators[fieldIndicatorName]

                const datasets: Array<ChartDataset> = []

                Object.keys(indicators).map(indicatorName => {
                    datasets.push({
                        label: indicatorName,
                        backgroundColor: graphAggregationColors[indicatorName] ?? defaultAggregationColor,
                        data: fieldIndicators[fieldIndicatorName][indicatorName]
                    })
                })

                graphs.push({
                    name: fieldIndicatorName,
                    data: {
                        labels: labels,
                        datasets: datasets
                    }
                })
            })

            state.graphs = graphs
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
