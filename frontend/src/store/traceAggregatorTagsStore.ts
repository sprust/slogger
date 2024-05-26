import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../utils/apiContainer.ts";
import {handleApiError} from "../utils/helpers.ts";

type TraceAggregatorFindTypesPayload = AdminApi.TraceAggregatorTracesContentTypesCreate.RequestBody;
type TraceAggregatorFindType = AdminApi.TraceAggregatorTracesContentTypesCreate.ResponseBody['data'][number];

type TraceAggregatorFindTagsPayload = AdminApi.TraceAggregatorTracesContentTagsCreate.RequestBody;
type TraceAggregatorFindTag = AdminApi.TraceAggregatorTracesContentTagsCreate.ResponseBody['data'][number];

type TraceAggregatorFindStatusesPayload = AdminApi.TraceAggregatorTracesContentStatusesCreate.RequestBody;
type TraceAggregatorFindStatus = AdminApi.TraceAggregatorTracesContentStatusesCreate.ResponseBody['data'][number];

export interface TraceTag {
    name: string;
    count: number;
}

interface State {
    loading: boolean,

    types: Array<TraceTag>,
    typesPayload: TraceAggregatorFindTypesPayload,

    tags: Array<TraceTag>,
    tagsPayload: TraceAggregatorFindTagsPayload,

    statuses: Array<TraceTag>,
    statusesPayload: TraceAggregatorFindStatusesPayload,
}

export const traceAggregatorFindTagsStore = createStore<State>({
    state: {
        loading: true,

        types: new Array<TraceTag>,
        typesPayload: {} as TraceAggregatorFindTagsPayload,

        tags: new Array<TraceTag>,
        tagsPayload: {} as TraceAggregatorFindTagsPayload,

        statuses: new Array<TraceTag>,
        statusesPayload: {} as TraceAggregatorFindStatusesPayload,
    } as State,
    mutations: {
        setTypes(state: State, types: Array<TraceAggregatorFindType>) {
            state.types = types
        },
        setTags(state: State, tags: Array<TraceAggregatorFindTag>) {
            state.tags = tags
        },
        setStatuses(state: State, statuses: Array<TraceAggregatorFindStatus>) {
            state.statuses = statuses
        },
    },
    actions: {
        async findTypes({commit, state}: { commit: any, state: State }) {
            state.loading = true
            state.types = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentTypesCreate(state.typesPayload)
                commit('setTypes', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
        async findTags({commit, state}: { commit: any, state: State }) {
            state.loading = true
            state.tags = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentTagsCreate(state.tagsPayload)

                commit('setTags', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
        async findStatuses({commit, state}: { commit: any, state: State }) {
            state.loading = true
            state.statuses = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentStatusesCreate(
                    state.statusesPayload
                )

                commit('setStatuses', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.loading = false
            }
        },
    },
})

export const traceAggregatorFindTagsStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTagsStore(): Store<State> {
    return baseUseStore(traceAggregatorFindTagsStoreInjectionKey)
}
