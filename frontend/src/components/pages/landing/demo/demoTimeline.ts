import type {ChartData, ChartDataset, ChartOptions} from 'chart.js'
import {
    defaultAggregationColor,
    graphAggregationColors,
    hiddenIndicatorsByDefault,
    makeTraceTimelineOptions
} from "../../trace-aggregator/components/graph/traceTimelineSeries.ts";

export interface DemoTimelineGraph {
    name: string,
    data: ChartData,
}

const counts: Array<number> = [
    42, 45, 39, 44, 47, 41, 46, 43, 44, 40,
    268, 291, 284, 276,
    48, 43, 45, 41, 44, 46, 42, 45, 40, 43,
]

const durationAvg: Array<number> = [
    0.31, 0.29, 0.33, 0.30, 0.32, 0.28, 0.31, 0.30, 0.29, 0.32,
    1.42, 1.67, 1.58, 1.49,
    0.34, 0.31, 0.30, 0.29, 0.32, 0.30, 0.31, 0.28, 0.30, 0.29,
]

function labels(): Array<string> {
    const result: Array<string> = []

    for (let index = 0; index < counts.length; index++) {
        const minutes = 13 * 60 + 30 + index * 5
        const hours = String(Math.floor(minutes / 60)).padStart(2, '0')
        const rest = String(minutes % 60).padStart(2, '0')

        result.push(`2026-09-15 ${hours}:${rest}:00`)
    }

    return result
}

function dataset(name: string, data: Array<number>): ChartDataset {
    return {
        label: name,
        backgroundColor: graphAggregationColors[name] ?? defaultAggregationColor,
        hidden: hiddenIndicatorsByDefault.includes(name),
        data: data,
    }
}

function scaled(values: Array<number>, factor: number): Array<number> {
    return values.map(value => Math.round(value * factor * 1000) / 1000)
}

export const demoTimelineGraphs: Array<DemoTimelineGraph> = [
    {
        name: 'count',
        data: {
            labels: labels(),
            datasets: [dataset('sum', counts)],
        },
    },
    {
        name: 'duration',
        data: {
            labels: labels(),
            datasets: [
                dataset('avg', durationAvg),
                dataset('min', scaled(durationAvg, 0.15)),
                dataset('max', scaled(durationAvg, 3.1)),
                dataset('p50', scaled(durationAvg, 0.8)),
                dataset('p95', scaled(durationAvg, 2.2)),
                dataset('p99', scaled(durationAvg, 2.8)),
            ],
        },
    },
]

export const demoTimelineOptions: ChartOptions = makeTraceTimelineOptions()
