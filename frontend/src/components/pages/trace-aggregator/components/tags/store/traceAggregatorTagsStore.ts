import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

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

interface TraceAggregatorTagsStoreInterface {
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

export const useTraceAggregatorTagsStore = defineStore('traceAggregatorTagsStore', {
    state: (): TraceAggregatorTagsStoreInterface => {
        return {
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
        }
    },
    actions: {
        async findTypes() {
            this.typesLoading.loading = true
            this.types = [];

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesContentTypesCreate(this.tagsPayload)
                    .then(response => {
                        this.setTypes(response.data.data)
                    })
                    .finally(() => {
                        this.typesLoading.loading = false
                    })
            )
        },
        async findTags() {
            this.tagsLoading.loading = true
            this.tags = [];

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesContentTagsCreate(this.tagsPayload)
                    .then(response => {
                        this.setTags(response.data.data)
                    })
                    .finally(() => {
                        this.tagsLoading.loading = false
                    })
            )
        },
        async findStatuses() {
            this.statusesLoading.loading = true
            this.statuses = [];

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesContentStatusesCreate(this.tagsPayload)
                    .then(response => {
                        this.setStatuses(response.data.data)
                    })
                    .finally(() => {
                        this.statusesLoading.loading = false
                    })
            )
        },
        setTypes(types: Array<TraceAggregatorFindType>) {
            this.types = types
        },
        setTags(tags: Array<TraceAggregatorFindTag>) {
            this.tags = tags
        },
        setStatuses(statuses: Array<TraceAggregatorFindStatus>) {
            this.statuses = statuses
        },
    },
})
