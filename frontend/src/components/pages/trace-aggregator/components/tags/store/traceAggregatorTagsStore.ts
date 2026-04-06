import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {TraceAggregatorCommonPayload, TraceAggregatorDataPayload} from "../../traces/store/traceAggregatorStore.ts";

type TraceAggregatorFindTypesPayload = TraceAggregatorCommonPayload & {
    text?: string | null,
    data?: TraceAggregatorDataPayload,
};
type TraceAggregatorFindType = AdminApi.TraceAggregatorTracesContentTypesCreate.ResponseBody['data'][number];

type TraceAggregatorFindTagsPayload = TraceAggregatorCommonPayload & {
    text?: string | null,
    data?: TraceAggregatorDataPayload,
};
type TraceAggregatorFindTag = AdminApi.TraceAggregatorTracesContentTagsCreate.ResponseBody['data'][number];

type TraceAggregatorFindStatusesPayload = TraceAggregatorCommonPayload & {
    text?: string | null,
    data?: TraceAggregatorDataPayload,
};
type TraceAggregatorFindStatus = AdminApi.TraceAggregatorTracesContentStatusesCreate.ResponseBody['data'][number];

export interface TraceTag {
    name: string;
    count: number;
}

export type TagLoading = {
    loading: boolean
}

export type TraceTagHistoryType = 'types' | 'tags' | 'statuses'

interface TraceTagHistoryState {
    types: string[],
    tags: string[],
    statuses: string[],
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

    showDialog: boolean,
    recentSelections: TraceTagHistoryState,
}

const RECENT_SELECTIONS_STORAGE_KEY = 'trace-aggregator-tag-recent-selections'
const RECENT_SELECTIONS_LIMIT = 10

function getDefaultRecentSelections(): TraceTagHistoryState {
    return {
        types: [],
        tags: [],
        statuses: [],
    }
}

function loadRecentSelections(): TraceTagHistoryState {
    if (typeof localStorage === 'undefined') {
        return getDefaultRecentSelections()
    }

    const savedSelections = localStorage.getItem(RECENT_SELECTIONS_STORAGE_KEY)

    if (!savedSelections) {
        return getDefaultRecentSelections()
    }

    try {
        const parsedSelections = JSON.parse(savedSelections) as Partial<TraceTagHistoryState>

        return {
            types: Array.isArray(parsedSelections.types) ? parsedSelections.types.filter(Boolean).slice(0, RECENT_SELECTIONS_LIMIT) : [],
            tags: Array.isArray(parsedSelections.tags) ? parsedSelections.tags.filter(Boolean).slice(0, RECENT_SELECTIONS_LIMIT) : [],
            statuses: Array.isArray(parsedSelections.statuses) ? parsedSelections.statuses.filter(Boolean).slice(0, RECENT_SELECTIONS_LIMIT) : [],
        }
    } catch {
        return getDefaultRecentSelections()
    }
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

            showDialog: false,
            recentSelections: loadRecentSelections(),
        }
    },
    actions: {
        async findTypes() {
            this.typesLoading.loading = true
            this.types = [];

            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesContentTypesCreate(
                    this.typesPayload as AdminApi.TraceAggregatorTracesContentTypesCreate.RequestBody
                )
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
                () => ApiContainer.get().traceAggregatorTracesContentTagsCreate(
                    this.tagsPayload as AdminApi.TraceAggregatorTracesContentTagsCreate.RequestBody
                )
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
                () => ApiContainer.get().traceAggregatorTracesContentStatusesCreate(
                    this.statusesPayload as AdminApi.TraceAggregatorTracesContentStatusesCreate.RequestBody
                )
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
        addRecentSelection(type: TraceTagHistoryType, value: string) {
            const normalizedValue = value.trim()

            if (!normalizedValue) {
                return
            }

            this.recentSelections[type] = [
                normalizedValue,
                ...this.recentSelections[type].filter((item: string) => item !== normalizedValue),
            ].slice(0, RECENT_SELECTIONS_LIMIT)

            this.saveRecentSelections()
        },
        saveRecentSelections() {
            if (typeof localStorage === 'undefined') {
                return
            }

            localStorage.setItem(
                RECENT_SELECTIONS_STORAGE_KEY,
                JSON.stringify(this.recentSelections)
            )
        },
    },
})
