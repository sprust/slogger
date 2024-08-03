import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {handleApiError, TypesHelper} from "../utils/helpers.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";

type TraceAggregatorRequest = AdminApi.TraceAggregatorTracesCreate.RequestBody;
type TraceAggregatorResponse = AdminApi.TraceAggregatorTracesCreate.ResponseBody['data'];

export type TraceAggregatorPayload = TraceAggregatorRequest

export type TraceAggregatorItems = TraceAggregatorResponse['items']
export type TraceAggregatorItem = TraceAggregatorResponse['items'][number]
export type TraceAggregatorAdditionalField = TraceAggregatorResponse['items'][number]['trace']['additional_fields'][number]

export type TraceAggregatorCustomField = {
    field: string,
    search: false,
    searchData: TraceAggregatorCustomFieldSearchParameter,
    sort: boolean,
    sortDirection: "asc" | "desc",
    addToTable: boolean,
    addToGraph: boolean,
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
}

export type TracesAddCustomFieldParameter = {
    field: string,
    data: TraceAggregatorCustomFieldSearchParameter
}

interface State {
    loading: boolean,
    payload: TraceAggregatorPayload,
    traceAggregator: TraceAggregatorResponse
    customFields: TraceAggregatorCustomField[]
}

export const traceAggregatorStore = createStore<State>({
    state: {
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
            logging_from: '',
            logging_to: '',
            trace_id: null,
            all_traces_in_tree: false,
            data: {
                filter: [],
                fields: []
            },
            sort: [
                {
                    field: "loggedAt",
                    direction: "desc"
                }
            ]
        } as TraceAggregatorPayload,
        traceAggregator: {} as TraceAggregatorResponse,
        customFields: []
    } as State,
    mutations: {
        setData(state: State, data: TraceAggregatorResponse) {
            state.traceAggregator = data
        },
        resetFilters(state: State) {
            state.payload = {
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
                logging_from: '',
                logging_to: '',
                trace_id: null,
                all_traces_in_tree: false,
                data: {
                    filter: [],
                    fields: []
                },
                sort: [
                    {
                        field: "loggedAt",
                        direction: "desc"
                    }
                ]
            }
            state.customFields = []
        },
        setPage(state: State, page: number) {
            state.payload!.page = page
        },
        setPerPage(state: State, perPage: number) {
            state.payload!.per_page = perPage
        },
        addOrDeleteType(state: State, type: string) {
            if (state.payload.types?.indexOf(type) === -1) {
                state.payload.types?.push(type)
            } else {
                state.payload.types = state.payload.types?.filter(
                    (typeItem: string) => typeItem !== type
                ) ?? []
            }
        },
        addOrDeleteTag(state: State, tag: string) {
            if (state.payload.tags?.indexOf(tag) === -1) {
                state.payload.tags?.push(tag)
            } else {
                state.payload.tags = state.payload.tags?.filter(
                    (tagItem: string) => tagItem !== tag
                ) ?? []
            }
        },
        addOrDeleteStatus(state: State, status: string) {
            if (state.payload.statuses?.indexOf(status) === -1) {
                state.payload.statuses?.push(status)
            } else {
                state.payload.statuses = state.payload.statuses?.filter(
                    (statusItem: string) => statusItem !== status
                ) ?? []
            }
        },
        addOrDeleteCustomField(state: State, parameters: TraceAggregatorCustomFieldParameter) {
            const customField = parameters.field

            const index = state.customFields.findIndex(
                (customFieldsItem: TraceAggregatorCustomField) => customFieldsItem.field === customField
            )

            if (index !== -1) {
                state.customFields.splice(index, 1)
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

                state.customFields.push({
                    field: customField,
                    search: false,
                    searchData: searchData.data,
                    sort: false,
                    sortDirection: "asc",
                    addToTable: false,
                    addToGraph: false,
                })
            }
        },
        prepareCommonPayloadData(state: State) {
            if (!state.payload.logging_from) {
                delete state.payload.logging_from
            }

            if (!state.payload.logging_to) {
                delete state.payload.logging_to
            }

            const data = state.payload.data!;

            data.filter = []
            data.fields = []
            state.payload.sort = []

            state.customFields.map((customField: TraceAggregatorCustomField) => {
                const field = `data.${customField.field}`

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
        clearDurationFilter(state: State) {
            state.payload.duration_from = null
            state.payload.duration_to = null
        },
        clearMemoryFilter(state: State) {
            state.payload.memory_from = null
            state.payload.memory_to = null
        },
        clearCpuFilter(state: State) {
            state.payload.cpu_from = null
            state.payload.cpu_to = null
        },
    },
    actions: {
        fillTraceAggregator({commit, state}: { commit: any, state: any }) {
            state.loading = true

            commit('prepareCommonPayloadData')

            state.customFields.map((customField: TraceAggregatorCustomField) => {
                const field = `data.${customField.field}`

                if (customField.sort) {
                    state.payload.sort!.push({
                        field: field,
                        direction: customField.sortDirection
                    })
                }

                if (customField.addToTable) {
                    state.payload.data.fields.push(customField.field)
                }
            });

            if (!state.payload.sort.length) {
                state.payload.sort = [
                    {
                        field: 'loggedAt',
                        direction: 'desc'
                    }
                ]
            }

            commit('setData', [])

            return ApiContainer.get().traceAggregatorTracesCreate(state.payload)
                .then((response) => {
                    commit('setData', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loading = false
                })
        },
        resetFilters({commit}: { commit: any }) {
            commit('resetFilters')
        },
        setPage({commit}: { commit: any }, page: number) {
            commit('setPage', page)
        },
        setPerPage({commit}: { commit: any }, perPage: number) {
            commit('setPerPage', perPage)
        },
        addOrDeleteType({commit}: { commit: any }, type: string) {
            commit('addOrDeleteType', type)
        },
        addOrDeleteTag({commit}: { commit: any }, tag: string) {
            commit('addOrDeleteTag', tag)
        },
        addOrDeleteStatus({commit}: { commit: any }, status: string) {
            commit('addOrDeleteStatus', status)
        },
        prepareCommonPayloadData({commit}: { commit: any }) {
            commit('prepareCommonPayloadData')
        },
        clearDurationFilter({commit}: { commit: any }) {
            commit('clearDurationFilter')
        }
    },
})

export const traceAggregatorStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorStore(): Store<State> {
    return baseUseStore(traceAggregatorStoreInjectionKey)
}
