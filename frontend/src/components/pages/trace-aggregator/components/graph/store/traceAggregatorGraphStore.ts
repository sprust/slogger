import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {convertDateStringToLocal} from "../../../../../../utils/helpers.ts";
import {ChartData, ChartDataset, ChartOptions} from 'chart.js'
import {TraceAggregatorCustomField} from "../../traces/store/traceAggregatorStore.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

type TraceAggregatorTraceMetricsPayload = AdminApi.TraceAggregatorTraceMetricsCreate.RequestBody
type TraceAggregatorTraceMetricField = AdminApi.TraceAggregatorTraceMetricsCreate.RequestBody['fields']
type TraceAggregatorTraceMetricResponse = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody
type TraceAggregatorTraceMetricItem = AdminApi.TraceAggregatorTraceMetricsCreate.ResponseBody['data']['items'][number]

interface GraphItem {
    name: string,
    data: ChartData
}

interface TraceAggregatorGraphStoreInterface {
    showGraph: boolean,
    playGraph: boolean,
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

export const useTraceAggregatorGraphStore = defineStore('traceAggregatorGraphStore', {
    state: (): TraceAggregatorGraphStoreInterface => {
        return {
            showGraph: false,
            playGraph: false,
            loading: false,
            waiting: false,

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
            },

            preTimestamp: null,
            preTimestampCounts: 0
        }
    },
    actions: {
        async findMetrics(
            fields: TraceAggregatorTraceMetricField,
            dataFields: null | Array<TraceAggregatorCustomField>
        ) {
            this.loading = true

            this.payload.fields = fields

            this.payload.data_fields = []

            dataFields?.forEach((customField: TraceAggregatorCustomField) => {
                if (!customField.addToGraph) {
                    return
                }

                this.payload.data_fields?.push(customField.field)
            })

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTraceMetricsCreate(this.payload)
                    .then(response => {
                        this.setMetrics(response.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        setMetrics(data: TraceAggregatorTraceMetricResponse) {
            this.loggedAtFrom = data.data.loggedAtFrom
            this.metrics = data.data.items

            if (!this.metrics.length) {
                this.graphs = []

                return
            }

            const labels: Array<string> = []

            const fieldIndicators: {
                [key: string]: {
                    [key: string]: Array<number>
                }
            } = {}

            this.metrics.forEach((item: TraceAggregatorTraceMetricItem) => {
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

            this.graphs = graphs
        }
    },
})
