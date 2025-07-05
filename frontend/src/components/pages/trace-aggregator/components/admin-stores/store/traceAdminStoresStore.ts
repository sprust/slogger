import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {TraceStateParameters, useTraceAggregatorStore} from "../../traces/store/traceAggregatorStore.ts";
import {makeGeneralFiltersTitles, makeGraphTitles, makeOtherFiltersTitles} from "../../../../../../utils/helpers.ts";
import {useTraceAggregatorServicesStore} from "../../services/store/traceAggregatorServicesStore.ts";
import {useTraceAggregatorTagsStore} from "../../tags/store/traceAggregatorTagsStore.ts";
import {
    TraceAggregatorTraceMetricsPayload,
    useTraceAggregatorGraphStore
} from "../../graph/store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampFieldsStore} from "../../graph/store/traceAggregatorTimestampFieldsStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../graph/store/traceAggregatorTimestampPeriodsStore.ts";

export type AdminStores = AdminApi.TraceAggregatorStatesList.ResponseBody['data']
export type AdminStore = AdminApi.TraceAggregatorStatesList.ResponseBody['data']['items'][number]
export type AdminStoresParameters = AdminApi.TraceAggregatorStatesList.RequestQuery
export type AdminStoreCreateParameters = AdminApi.TraceAggregatorStatesCreate.RequestBody

// need for admin stores
const stateVersion = 3

interface DeletedIds {
    [key: string]: boolean
}

interface AdminStoresStoreInterface {
    loading: boolean
    findParameters: AdminStoresParameters
    adminStores: AdminStores
    createParameters: AdminStoreCreateParameters
    deletedIds: DeletedIds
}

interface StoreItemsInterface {
    traces: TraceStateParameters,
    tags: {
        showDialog: boolean
    },
    graph: {
        showGraph: boolean,
        payload: TraceAggregatorTraceMetricsPayload,
        selectedFields: Array<string>,
        selectedTimestampPeriod: string,
        selectedTimestampStep: string,
    }
}

export const useTraceAdminStoresStore = defineStore('traceAdminStoresStore', {
    state: (): AdminStoresStoreInterface => {
        return {
            loading: true,
            findParameters: {
                page: 1,
                version: 0,
                search_query: '',
                auto: false
            },
            adminStores: {
                items: [],
                paginator: {
                    total: 0,
                    total_pages: 0,
                    per_page: 0,
                    current_page: 1
                }
            },
            createParameters: {
                title: '',
                store_version: 0,
                store_data: '',
                auto: false
            },
            deletedIds: {}
        }
    },
    getters: {
        traceAggregatorStore() {
            return useTraceAggregatorStore()
        },
        traceAggregatorServicesStore() {
            return useTraceAggregatorServicesStore()
        },
        traceAggregatorTagsStore() {
            return useTraceAggregatorTagsStore()
        },
        traceAggregatorGraphStore() {
            return useTraceAggregatorGraphStore()
        },
        traceAggregatorTimestampFieldsStore() {
            return useTraceAggregatorTimestampFieldsStore()
        },
        traceAggregatorTimestampPeriodStore() {
            return useTraceAggregatorTimestampPeriodStore()
        }
    },
    actions: {
        async findAdminStores() {
            this.loading = true

            this.findParameters.version = stateVersion

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorStatesList(this.findParameters)
                    .then(response => {
                        this.deletedIds = {}
                        this.adminStores = response.data.data
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        clearAdminStoreCreateParameters() {
            this.createParameters = {
                title: '',
                store_version: 0,
                store_data: '',
                auto: false
            }
        },
        async create(auto: boolean) {
            if (auto) {
                this.createParameters.title = this.generateStoreTitle()
            }

            if (!this.createParameters.title) {
                return
            }

            this.createParameters.store_version = stateVersion
            this.createParameters.store_data = this.serializeTraceState()
            this.findParameters.auto = auto
            this.createParameters.auto = auto

            return this.createAdminStore()

        },
        async createAdminStore() {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorStatesCreate(this.createParameters)
            )
        },
        async deleteAdminStore(id: string) {
            return await handleApiRequest(
                ApiContainer.get().traceAggregatorStatesDelete(id)
                    .then(() => {
                        this.deletedIds[id] = true

                        this.loading = false
                    })
            )
        },
        generateStoreTitle(): string {
            const titles: string[] = [
                ...makeGeneralFiltersTitles(this.traceAggregatorStore, this.traceAggregatorServicesStore.items),
                ...makeOtherFiltersTitles(this.traceAggregatorStore.payload),
                ...makeGraphTitles(
                    this.traceAggregatorGraphStore.showGraph,
                    this.traceAggregatorTimestampFieldsStore.selectedFields,
                    this.traceAggregatorTimestampPeriodStore.selectedTimestampPeriod,
                    this.traceAggregatorTimestampPeriodStore.selectedTimestampStep,
                ),
            ]

            return titles.join(' | ')
        },
        serializeTraceState() {
            const state: StoreItemsInterface = {
                traces: {
                    payload: this.traceAggregatorStore.payload,
                    customFields: this.traceAggregatorStore.customFields,
                },
                tags: {
                    showDialog: this.traceAggregatorTagsStore.showDialog
                },
                graph: {
                    showGraph: this.traceAggregatorGraphStore.showGraph,
                    payload: this.traceAggregatorGraphStore.payload,
                    selectedFields: this.traceAggregatorTimestampFieldsStore.selectedFields,
                    selectedTimestampPeriod: this.traceAggregatorTimestampPeriodStore.selectedTimestampPeriod,
                    selectedTimestampStep: this.traceAggregatorTimestampPeriodStore.selectedTimestampStep,
                }
            }

            return JSON.stringify(state)
        },
        restoreTraceState(store: AdminStore) {
            const state: StoreItemsInterface = JSON.parse(store.store_data)

            // traces
            this.traceAggregatorStore.payload = state.traces.payload
            this.traceAggregatorStore.customFields = state.traces.customFields

            // tags
            this.traceAggregatorTagsStore.showDialog = state.tags.showDialog

            // graph
            this.traceAggregatorGraphStore.showGraph = state.graph.showGraph
            this.traceAggregatorGraphStore.payload = state.graph.payload
            this.traceAggregatorTimestampFieldsStore.selectedFields = state.graph.selectedFields
            this.traceAggregatorTimestampPeriodStore.selectedTimestampPeriod = state.graph.selectedTimestampPeriod
            this.traceAggregatorTimestampPeriodStore.selectedTimestampStep = state.graph.selectedTimestampStep
        },
    },
})
