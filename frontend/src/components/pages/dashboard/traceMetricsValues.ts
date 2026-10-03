import type {DashboardTraceMetric} from "./store/dashboardMetricsStore.ts";
import {formatUtcDateTime, utcTimestamp} from "../../../utils/utcDateTime.ts";

export const metricSlotMinutes = 15

export const metricSlotMs = metricSlotMinutes * 60 * 1000

export interface MetricTotals {
    logged: number,
    buffered: number,
    stored: number,
}

export interface MetricType {
    name: string,
    stored: number,
}

export interface MetricValues {
    logged: Array<number>,
    buffered: Array<number>,
    stored: Array<number>,
}

export interface MetricSeries {
    key: keyof MetricTotals,
    label: string,
    color: string,
}

export const metricSeries: Array<MetricSeries> = [
    {key: 'logged', label: 'logged', color: 'rgba(121,146,248,0.9)'},
    {key: 'buffered', label: 'buffered', color: 'rgb(246,188,2)'},
    {key: 'stored', label: 'stored', color: 'rgba(163,248,121,0.9)'},
]

export function slotOf(timestamp: string): string {
    const at = utcTimestamp(timestamp)

    return at === null ? timestamp : formatUtcDateTime(new Date(Math.floor(at / metricSlotMs) * metricSlotMs))
}

export function makeMetricTypes(rows: Array<DashboardTraceMetric>): Array<MetricType> {
    const stored: Record<string, number> = {}

    rows.forEach((row: DashboardTraceMetric) => {
        stored[row.type] = (stored[row.type] ?? 0) + row.stored
    })

    return Object.keys(stored)
        .map(name => ({name, stored: stored[name]}))
        .sort((a: MetricType, b: MetricType) => b.stored - a.stored || a.name.localeCompare(b.name))
}

export function filterMetricRows(
    rows: Array<DashboardTraceMetric>,
    selectedTypes: Array<string>,
): Array<DashboardTraceMetric> {
    if (!selectedTypes.length) {
        return rows
    }

    return rows.filter((row: DashboardTraceMetric) => selectedTypes.includes(row.type))
}

export function makeMetricValues(slots: Array<string>, rows: Array<DashboardTraceMetric>): MetricValues {
    const indexes: Record<string, number> = {}

    slots.forEach((slot: string, index: number) => {
        indexes[slot] = index
    })

    const values: MetricValues = {
        logged: new Array(slots.length).fill(0),
        buffered: new Array(slots.length).fill(0),
        stored: new Array(slots.length).fill(0),
    }

    rows.forEach((row: DashboardTraceMetric) => {
        const index = indexes[slotOf(row.timestamp)]

        if (index === undefined) {
            return
        }

        values.logged[index] += row.logged
        values.buffered[index] += row.buffered
        values.stored[index] += row.stored
    })

    return values
}

export function makeMetricTotals(values: MetricValues): MetricTotals {
    const sum = (items: Array<number>) => items.reduce((total, item) => total + item, 0)

    return {
        logged: sum(values.logged),
        buffered: sum(values.buffered),
        stored: sum(values.stored),
    }
}
