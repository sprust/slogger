import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {makeStartOfDay, normalizeUtcDateTime, TypesHelper} from "../../../../../../utils/helpers.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {useTraceAggregatorServicesStore} from "../../services/store/traceAggregatorServicesStore.ts";

type TraceAggregatorResponse = AdminApi.TraceAggregatorTracesCreate.ResponseBody['data'];

export type TraceAggregatorPayload = TraceAggregatorRequest

export type TraceAggregatorItems = TraceAggregatorResponse['items']
export type TraceAggregatorItem = TraceAggregatorResponse['items'][number]
export type TraceAggregatorAdditionalField = TraceAggregatorResponse['items'][number]['trace']['additional_fields'][number]

export type TraceAggregatorCustomField = {
    field: string,
    canBeFiltered: boolean,
    search: false,
    searchData: TraceAggregatorCustomFieldSearchParameter,
    addToTable: boolean,
    addToGraph: boolean,
}

// TODO: get comp from scheme
export enum PeriodPresetEnum {
    Custom = 'custom',
    LastHour = 'last_hour',
    Last2Hours = 'last_2_hours',
    Last3Hours = 'last_3_hours',
    Last6Hours = 'last_6_hours',
    Last12Hours = 'last_12_hours',
    LastDay = 'last_day',
    Last3Days = 'last_3_days',
    LastWeek = 'last_week',
    Last2Weeks = 'last_2_weeks',
    LastMonth = 'last_month',
}

export function getPeriodPresetEnumByValue(value: string) {
    return PeriodPresetEnum[
        Object.entries(PeriodPresetEnum)
            .find(([_, v]) => v === value)?.[0] as keyof typeof PeriodPresetEnum
        ];
}

export type TraceAggregatorDataFilter = {
    field?: string,
    null?: boolean,
    numeric?: {
        value?: number,
        comp?: "=" | "!=" | ">" | ">=" | "<" | "<="
    },
    string?: {
        value?: string,
        comp?: "equals" | "contains" | "starts" | "ends"
    },
    boolean?: {
        value?: boolean,
    },
}

export type TraceAggregatorDataPayload = {
    filter?: TraceAggregatorDataFilter[],
    fields?: string[],
}

export interface TraceAggregatorCommonPayload {
    service_ids?: number[],
    trace_id?: string | null,
    all_traces_in_tree?: boolean,
    logging_from_preset?: PeriodPresetEnum,
    logging_from?: string,
    logging_to?: string,
    types?: string[],
    tags?: string[],
    statuses?: string[],
    duration_from?: number | null,
    duration_to?: number | null,
    memory_from?: number | null,
    memory_to?: number | null,
    cpu_from?: number | null,
    cpu_to?: number | null,
    data?: TraceAggregatorDataPayload,
    has_profiling?: boolean,
}

type TraceAggregatorRequest = TraceAggregatorCommonPayload & {
    page: number,
    per_page?: number,
}

// TODO: get comp from scheme
export type TraceAggregatorCustomFieldSearchParameter = {
    null: {
        enabled: boolean
        value: boolean
    },
    number?: {
        value: number,
        comp: "=" | "!=" | ">" | ">=" | "<" | "<="
    },
    string?: {
        value: string,
        comp: "equals" | "contains" | "starts" | "ends"
    },
    boolean?: {
        value: boolean,
    },
}

export type TraceAggregatorCustomFieldParameter = {
    field: string,
    value: any,
    canBeFiltered: boolean
}

export type TracesAddCustomFieldParameter = {
    field: string,
    data: TraceAggregatorCustomFieldSearchParameter
}

interface State extends TraceStateParameters {
    loading: boolean,
    traceAggregator: TraceAggregatorResponse
}

export interface TraceStateParameters {
    payload: TraceAggregatorPayload,
    customFields: TraceAggregatorCustomField[],
}

export const useTraceAggregatorStore = defineStore('traceAggregatorStore', {
    state: (): State => {
        return {
            loading: true,
            payload: {
                page: 1,
                types: [],
                tags: [],
                statuses: [],
                duration_from: null,
                duration_to: null,
                memory_from: null,
                memory_to: null,
                cpu_from: null,
                cpu_to: null,
                logging_from_preset: PeriodPresetEnum.LastHour,
                logging_from: '',
                logging_to: '',
                trace_id: null,
                all_traces_in_tree: false,
                data: {
                    filter: [],
                    fields: []
                },
            } as TraceAggregatorPayload,
            traceAggregator: {} as TraceAggregatorResponse,
            customFields: [],
        }
    },
    getters: {
        traceAggregatorServicesStore() {
            return useTraceAggregatorServicesStore()
        }
    },
    actions: {
        async fillTraceAggregator() {
            this.loading = true

            this.prepareCommonPayloadData()

            this.customFields.map((customField: TraceAggregatorCustomField) => {
                if (customField.addToTable) {
                    this.payload.data!.fields!.push(customField.field)
                }
            });

            this.setData({} as TraceAggregatorResponse)

            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesCreate(
                    this.payload as AdminApi.TraceAggregatorTracesCreate.RequestBody
                )
                    .then((response) => {
                        this.setData(response.data.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        setData(data: TraceAggregatorResponse) {
            this.traceAggregator = data
        },
        resetFilters() {
            this.payload = {
                page: 1,
                types: [],
                tags: [],
                statuses: [],
                duration_from: null,
                duration_to: null,
                memory_from: null,
                memory_to: null,
                cpu_from: null,
                cpu_to: null,
                logging_from_preset: PeriodPresetEnum.LastHour,
                logging_from: normalizeUtcDateTime(makeStartOfDay()) ?? '',
                logging_to: '',
                trace_id: null,
                all_traces_in_tree: false,
                data: {
                    filter: [],
                    fields: []
                },
            }
            this.customFields = []
        },
        setPage(page: number) {
            this.payload!.page = page
        },
        addOrDeleteType(type: string) {
            if (this.payload.types?.indexOf(type) === -1) {
                this.payload.types?.push(type)
            } else {
                this.payload.types = this.payload.types?.filter(
                    (typeItem: string) => typeItem !== type
                ) ?? []
            }
        },
        addOrDeleteTag(tag: string) {
            if (this.payload.tags?.indexOf(tag) === -1) {
                this.payload.tags?.push(tag)
            } else {
                this.payload.tags = this.payload.tags?.filter(
                    (tagItem: string) => tagItem !== tag
                ) ?? []
            }
        },
        addOrDeleteStatus(status: string) {
            if (this.payload.statuses?.indexOf(status) === -1) {
                this.payload.statuses?.push(status)
            } else {
                this.payload.statuses = this.payload.statuses?.filter(
                    (statusItem: string) => statusItem !== status
                ) ?? []
            }
        },
        addOrDeleteCustomField(parameters: TraceAggregatorCustomFieldParameter) {
            const customField = parameters.field

            const index = this.customFields.findIndex(
                (customFieldsItem: TraceAggregatorCustomField) => customFieldsItem.field === customField
            )

            if (index !== -1) {
                this.customFields.splice(index, 1)
            } else {
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

                const searchData: TracesAddCustomFieldParameter = {
                    field: customField,
                    data: data
                }

                this.customFields.push({
                    field: customField,
                    canBeFiltered: parameters.canBeFiltered,
                    search: false,
                    searchData: searchData.data,
                    addToTable: false,
                    addToGraph: false,
                })
            }
        },
        prepareCommonPayloadData() {
            if (!this.payload.logging_from) {
                delete this.payload.logging_from
            } else {
                this.payload.logging_from = normalizeUtcDateTime(this.payload.logging_from)
            }

            if (!this.payload.logging_to) {
                delete this.payload.logging_to
            } else {
                this.payload.logging_to = normalizeUtcDateTime(this.payload.logging_to)
            }

            const data = this.payload.data!;

            data.filter = []
            data.fields = []

            this.customFields.map((customField: TraceAggregatorCustomField) => {
                const field = `dt.${customField.field}`

                if (customField.search) {
                    if (customField.searchData.null.enabled) {
                        data.filter!.push({
                            field: field,
                            null: customField.searchData.null.value
                        })
                    } else {
                        if (customField.searchData.number) {
                            data.filter!.push({
                                field: field,
                                numeric: {
                                    value: customField.searchData.number.value,
                                    comp: customField.searchData.number.comp,
                                }
                            })
                        } else if (customField.searchData.string) {
                            data.filter!.push({
                                field: field,
                                string: {
                                    value: customField.searchData.string.value,
                                    comp: customField.searchData.string.comp,
                                }
                            })
                        } else if (customField.searchData.boolean) {
                            data.filter!.push({
                                field: field,
                                boolean: {
                                    value: customField.searchData.boolean.value
                                }
                            })
                        }
                    }
                }
            });
        },
        clearDurationFilter() {
            this.payload.duration_from = null
            this.payload.duration_to = null
        },
        clearMemoryFilter() {
            this.payload.memory_from = null
            this.payload.memory_to = null
        },
        clearCpuFilter() {
            this.payload.cpu_from = null
            this.payload.cpu_to = null
        },

    },
})
