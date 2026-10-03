import type {
    TraceAggregatorCustomField,
    TraceAggregatorItem,
} from "../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import type {DemoJson, DemoTrace} from "./demoTraces.ts";

export interface DemoTraceFilter {
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

export function findPathValues(data: DemoJson, path: string): DemoPathValues {
    let current: Array<DemoJson> = [data]

    for (const segment of path.split('.')) {
        const next: Array<DemoJson> = []

        current.forEach((value: DemoJson) => {
            const objects = Array.isArray(value) ? value.filter(isObject) : (isObject(value) ? [value] : [])

            objects.forEach(object => {
                if (segment in object) {
                    next.push(object[segment])
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
        values: current.flatMap((value: DemoJson) => Array.isArray(value) ? value : [value]),
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

export function filterDemoTraces(traces: Array<DemoTrace>, filter: DemoTraceFilter): Array<TraceAggregatorItem> {
    const tableFields = filter.customFields
        .filter(customField => customField.addToTable && customField.field.trim() !== '')
        .map(customField => customField.field.trim())

    return traces
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
