import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../utils/apiContainer.ts";
import {convertDateStringToLocal, handleApiError} from "../utils/helpers.ts";
import {ChartData, ChartOptions} from 'chart.js'

type TraceAggregatorTraceMetricsPayload = AdminApi.TraceAggregatorTraceMetricsCreate.RequestBody;
type TraceAggregatorTraceMetricResponse = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody;
type TraceAggregatorTraceMetricItem = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody['data']['items'][number];

interface State {
    showGraph: boolean,
    loading: boolean,

    metrics: Array<TraceAggregatorTraceMetricItem>,
    payload: TraceAggregatorTraceMetricsPayload,

    graphData: ChartData,
    graphOptions: ChartOptions,

    lastTimestamp: string | null,
}

export const traceAggregatorGraphStore = createStore<State>({
    state: {
        showGraph: false,
        loading: false,

        metrics: new Array<TraceAggregatorTraceMetricItem>,
        payload: {} as TraceAggregatorTraceMetricsPayload,

        graphData: {
            labels: [],
            datasets: []
        } as ChartData,
        graphOptions: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false
        } as ChartOptions,

        lastTimestamp: null
    } as State,
    mutations: {
        setMetrics(state: State, data: TraceAggregatorTraceMetricResponse) {
            state.metrics = data.data.items

            if (!state.metrics.length) {
                state.graphData = {
                    labels: [],
                    datasets: [],
                }

                return
            }

            const timestamp = state.metrics[state.metrics.length - 1].timestamp;

            if (state.lastTimestamp === timestamp) {
                return;
            }

            state.lastTimestamp = timestamp

            const labels: Array<string> = []
            const datasetData: Array<number> = []

            state.metrics.forEach((item: TraceAggregatorTraceMetricItem) => {
                labels.push(
                    convertDateStringToLocal(item.timestamp, false)
                )
                datasetData.push(
                    item.count
                )
            })

            state.graphData = {
                labels: labels,
                datasets: [
                    {
                        label: 'graph',
                        backgroundColor: '#99f879',
                        data: datasetData
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
