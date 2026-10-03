import type {
    TraceAggregatorCustomField,
    TraceAggregatorCustomFieldParameter,
    TraceAggregatorCustomFieldSearchParameter,
    TraceAggregatorCustomFieldType,
} from "./store/traceAggregatorStore.ts";
import {TypesHelper} from "../../../../../utils/typesHelper.ts";

export function makeCustomFieldSearchData(type: TraceAggregatorCustomFieldType): TraceAggregatorCustomFieldSearchParameter {
    const data: TraceAggregatorCustomFieldSearchParameter = {
        null: {
            enabled: false,
            value: false
        }
    }

    switch (type) {
        case 'int':
            data.number = {value: 0, comp: '='}
            break
        case 'float':
            data.number = {value: 0, comp: '=', float: true}
            break
        case 'bool':
            data.boolean = {value: false}
            break
        default:
            data.string = {value: '', comp: 'equals'}
    }

    return data
}

export function makeEmptyCustomField(): TraceAggregatorCustomField {
    return {
        field: '',
        canBeFiltered: true,
        search: false,
        searchData: makeCustomFieldSearchData('string'),
        addToTable: false,
        addToGraph: false,
        manual: true,
    }
}

export function setCustomFieldType(customField: TraceAggregatorCustomField, type: TraceAggregatorCustomFieldType) {
    customField.searchData = {
        ...makeCustomFieldSearchData(type),
        null: customField.searchData.null,
        exists: customField.searchData.exists,
    }

    if (type !== 'int' && type !== 'float') {
        customField.addToGraph = false
    }
}

export function addOrDeleteCustomField(
    customFields: Array<TraceAggregatorCustomField>,
    parameters: TraceAggregatorCustomFieldParameter
) {
    const customField = parameters.field

    const index = customFields.findIndex(
        (customFieldsItem: TraceAggregatorCustomField) => customFieldsItem.field === customField
    )

    if (index !== -1) {
        customFields.splice(index, 1)

        return
    }

    const data: TraceAggregatorCustomFieldSearchParameter = {
        null: {
            enabled: false,
            value: false
        }
    }

    const value = parameters.value

    if (TypesHelper.isValueInt(value) || TypesHelper.isValueFloat(value)) {
        data.number = {
            value: value,
            comp: "="
        }
    } else if (TypesHelper.isValueBool(value)) {
        data.boolean = {
            value: value
        }
    } else {
        data.string = {
            value: value,
            comp: "equals"
        }
    }

    customFields.push({
        field: customField,
        canBeFiltered: parameters.canBeFiltered,
        search: false,
        searchData: data,
        addToTable: false,
        addToGraph: false,
    })
}
