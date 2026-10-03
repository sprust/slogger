import type {
    TraceAggregatorCustomField,
    TraceAggregatorItem,
} from "../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import type {DemoJson, DemoTrace} from "./demoTraces.ts";

export interface DemoTraceFilter {
    traceId: string,
    serviceIds: Array<number>,
    types: Array<string>,
    tags: Array<string>,
    statuses: Array<string>,
    customFields: Array<TraceAggregatorCustomField>,
}

export interface DemoPathValues {
    found: boolean,
    values: Array<DemoJson>,
}

function isObject(value: DemoJson): value is { [key: string]: DemoJson } {
    return value !== null && typeof value === 'object' && !Array.isArray(value)
}

interface DemoPathItem {
    value: DemoJson,
    insideArray: boolean,
}

export function findPathValues(data: DemoJson, path: string): DemoPathValues {
    let current: Array<DemoPathItem> = [{value: data, insideArray: false}]

    for (const segment of path.split('.')) {
        const next: Array<DemoPathItem> = []

        current.forEach((item: DemoPathItem) => {
            const objects: Array<DemoPathItem> = []

            if (Array.isArray(item.value)) {
                if (!item.insideArray) {
                    item.value.filter(isObject).forEach(element => objects.push({value: element, insideArray: true}))
                }
            } else if (isObject(item.value)) {
                objects.push(item)
            }

            objects.forEach((object: DemoPathItem) => {
                const value = object.value as { [key: string]: DemoJson }

                if (segment in value) {
                    next.push({value: value[segment], insideArray: object.insideArray})
                }
            })
        })

        if (!next.length) {
            return {found: false, values: []}
        }

        current = next
    }

    return {
        found: true,
        values: current.flatMap((item: DemoPathItem) => Array.isArray(item.value) && !item.insideArray ? item.value : [item.value]),
    }
}

function compareNumber(value: number, comp: string, expected: number): boolean {
    switch (comp) {
        case '=':
            return value === expected
        case '!=':
            return value !== expected
        case '>':
            return value > expected
        case '>=':
            return value >= expected
        case '<':
            return value < expected
        default:
            return value <= expected
    }
}

function compareString(value: string, comp: string, expected: string): boolean {
    switch (comp) {
        case 'equals':
            return value === expected
        case 'not_equals':
            return value !== expected
        case 'contains':
            return value.includes(expected)
        case 'starts':
            return value.startsWith(expected)
        default:
            return value.endsWith(expected)
    }
}

export function matchesCustomField(data: DemoJson, customField: TraceAggregatorCustomField): boolean {
    const field = customField.field.trim()

    if (!customField.search || field === '' || !customField.canBeFiltered) {
        return true
    }

    if (!isObject(data)) {
        return false
    }

    const {found, values} = findPathValues(data, field)
    const search = customField.searchData

    if (search.exists?.enabled) {
        return found === search.exists.value
    }

    if (search.null.enabled) {
        return found && values.some(value => (value === null) === search.null.value)
    }

    if (search.number) {
        const number = search.number

        return values.some(value => typeof value === 'number' && compareNumber(value, number.comp, number.value))
    }

    if (search.boolean) {
        const expected = search.boolean.value

        return values.some(value => value === expected)
    }

    if (search.string) {
        const string = search.string

        return values.some(value => typeof value === 'string' && compareString(value, string.comp, string.value))
    }

    return true
}

function stringifyValue(value: DemoJson): string {
    return typeof value === 'string' ? value : JSON.stringify(value)
}

function treeRootOf(traces: Array<DemoTrace>, traceId: string): string {
    const parents: Record<string, string | null | undefined> = {}

    traces.forEach((trace: DemoTrace) => {
        parents[trace.item.trace.trace_id] = trace.item.trace.parent_trace_id
    })

    let current = traceId

    while (parents[current]) {
        current = parents[current] as string
    }

    return current
}

export function filterDemoTraces(traces: Array<DemoTrace>, filter: DemoTraceFilter): Array<TraceAggregatorItem> {
    const treeRoot = filter.traceId ? treeRootOf(traces, filter.traceId) : null

    const tableFields = filter.customFields
        .filter(customField => customField.addToTable && customField.field.trim() !== '')
        .map(customField => customField.field.trim())

    return traces
        .filter(trace => treeRoot === null || treeRootOf(traces, trace.item.trace.trace_id) === treeRoot)
        .filter(trace => !filter.serviceIds.length || filter.serviceIds.includes(trace.item.trace.service?.id ?? 0))
        .filter(trace => !filter.types.length || filter.types.includes(trace.item.trace.type))
        .filter(trace => !filter.tags.length || filter.tags.every(tag => trace.item.trace.tags.includes(tag)))
        .filter(trace => !filter.statuses.length || filter.statuses.includes(trace.item.trace.status))
        .filter(trace => filter.customFields.every(customField => matchesCustomField(trace.data, customField)))
        .map(trace => ({
            trace: {
                ...trace.item.trace,
                additional_fields: tableFields.map(field => ({
                    key: field,
                    values: findPathValues(trace.data, field).values.map(stringifyValue),
                })),
            },
        }))
}
