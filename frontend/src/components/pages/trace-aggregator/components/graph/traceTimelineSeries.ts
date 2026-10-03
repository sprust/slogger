import type {ChartOptions} from 'chart.js'

export interface AggregationColors {
    [key: string]: string
}

export const defaultAggregationColor: string = 'rgba(248,189,121)'

export const graphAggregationColors: AggregationColors = {
    sum: 'rgba(163,248,121)',
    avg: 'rgb(246,188,2)',
    min: 'rgb(0,48,255)',
    max: 'rgb(246,2,2)',
    p50: 'rgb(121,248,233)',
    p95: 'rgb(186,121,248)',
    p99: 'rgb(248,121,186)',
}

/**
 * The percentiles arrive with every answer and are drawn only when asked for: the chart
 * keeps the three series it always had, and the legend is where the other three are
 * turned on.
 *
 * This is the state a series starts in and nothing more. A legend click writes to the
 * chart's own dataset meta, which wins over this and belongs to that one chart — so
 * showing p95 on the duration graph leaves the memory graph alone, and a poll that
 * replaces the data leaves the choice standing.
 */
export const hiddenIndicatorsByDefault: Array<string> = ['p50', 'p95', 'p99']

export function makeTraceTimelineOptions(): ChartOptions {
    return {
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
    }
}
