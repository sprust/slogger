import type {
    TraceAggregatorCustomField,
    TraceAggregatorCustomFieldSearchParameter,
} from "../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";

function field(path: string, searchData: TraceAggregatorCustomFieldSearchParameter, addToTable: boolean): TraceAggregatorCustomField {
    return {
        field: path,
        canBeFiltered: true,
        search: true,
        searchData: searchData,
        addToTable: addToTable,
        addToGraph: false,
        manual: true,
    }
}

const notNull = {enabled: false, value: false}

export function makeFilterExample(name: string): Array<TraceAggregatorCustomField> {
    switch (name) {
        case 'items':
            return [field('invoice.items.price', {null: notNull, number: {value: 10000, comp: '>'}}, true)]
        case 'roles':
            return [field('user.roles', {null: notNull, string: {value: 'admin', comp: 'equals'}}, true)]
        case 'error':
            return [field('error.code', {null: notNull, exists: {enabled: true, value: true}, string: {value: '', comp: 'equals'}}, true)]
        case 'manager':
            return [field('client.manager', {null: {enabled: true, value: true}, string: {value: '', comp: 'equals'}}, false)]
        case 'status':
            return [field('response.status', {null: notNull, number: {value: 500, comp: '>='}}, true)]
        default:
            return []
    }
}
