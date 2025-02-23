import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

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

export type TagLoading = {
    loading: boolean
}

interface State {
    types: Array<TraceTag>,
    typesPayload: TraceAggregatorFindTypesPayload,
    typesLoading: TagLoading,

    tags: Array<TraceTag>,
    tagsPayload: TraceAggregatorFindTagsPayload,
    tagsLoading: TagLoading,

    statuses: Array<TraceTag>,
    statusesPayload: TraceAggregatorFindStatusesPayload,
    statusesLoading: TagLoading,
}

export const traceAggregatorFindTagsStore = createStore<State>({
    state: {
        loading: true,

        types: new Array<TraceTag>,
        typesPayload: {} as TraceAggregatorFindTagsPayload,
        typesLoading: {
            loading: false
        },

        tags: new Array<TraceTag>,
        tagsPayload: {} as TraceAggregatorFindTagsPayload,
        tagsLoading: {
            loading: false
        },

        statuses: new Array<TraceTag>,
        statusesPayload: {} as TraceAggregatorFindStatusesPayload,
        statusesLoading: {
            loading: false
        },
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
            state.typesLoading.loading = true
            state.types = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentTypesCreate(state.typesPayload)
                commit('setTypes', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.typesLoading.loading = false
            }
        },
        async findTags({commit, state}: { commit: any, state: State }) {
            state.tagsLoading.loading = true
            state.tags = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentTagsCreate(state.tagsPayload)

                commit('setTags', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.tagsLoading.loading = false
            }
        },
        async findStatuses({commit, state}: { commit: any, state: State }) {
            state.statusesLoading.loading = true
            state.statuses = [];

            try {
                const response = await ApiContainer.get().traceAggregatorTracesContentStatusesCreate(
                    state.statusesPayload
                )

                commit('setStatuses', response.data.data)
            } catch (error) {
                handleApiError(error)
            } finally {
                state.statusesLoading.loading = false
            }
        },
    },
})

export const traceAggregatorFindTagsStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTagsStore(): Store<State> {
    return baseUseStore(traceAggregatorFindTagsStoreInjectionKey)
}
