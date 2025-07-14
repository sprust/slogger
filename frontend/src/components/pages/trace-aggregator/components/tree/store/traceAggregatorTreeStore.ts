import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {readStream} from "../../../../../../utils/helpers.ts";
import {TreeBuilder} from "./TreeBuilder.ts";
import {TreeFilter} from "./TreeFilter.ts";

type TraceAggregatorTreeParameters = AdminApi.TraceAggregatorTracesTreeCreate.RequestBody
export type TraceAggregatorTree = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data']
export type TraceAggregatorTreeRow = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data'][number]

type TraceAggregatorTreeContentParameters = AdminApi.TraceAggregatorTracesTreeContentCreate.RequestBody
type TraceAggregatorTreeContent = AdminApi.TraceAggregatorTracesTreeContentCreate.ResponseBody['data']
type TraceAggregatorTreeContentService = AdminApi.TraceAggregatorTracesTreeContentCreate.ResponseBody['data']['services'][number]

interface ServicesMapInterface {
    [key: number]: TraceAggregatorTreeContentService
}

export interface TraceTreeNode {
    id: string,
    depth: number,
    primary: TraceAggregatorTreeRow,
    children: Array<TraceTreeNode>,
    collapsed: boolean,
    isHiddenByFilter: boolean,
}

interface TraceAggregatorTreeStoreInterface {
    loading: boolean,
    parameters: TraceAggregatorTreeParameters,
    tree: Array<TraceTreeNode>,
    treeNodes: Array<TraceAggregatorTreeRow>,
    content: TraceAggregatorTreeContent,
    servicesMap: ServicesMapInterface,
    dataLoading: boolean,
    selectedTrace: TraceAggregatorDetail,
    selectedTraceTypes: Array<string>,
    selectedTraceTags: Array<string>,
    selectedTraceStatuses: Array<string>,
    selectedTraceServiceIds: Array<number>,
    traceTotalIndicatorsNumber: number,
    traceIndicatingIds: Array<string>,
}

export const useTraceAggregatorTreeStore = defineStore('traceAggregatorTreeStore', {
    state: (): TraceAggregatorTreeStoreInterface => {
        return {
            loading: false,
            parameters: {} as TraceAggregatorTreeParameters,
            tree: new Array<TraceTreeNode>,
            treeNodes: new Array<TraceAggregatorTreeRow>,
            content: {
                count: 0,
                services: [],
                types: [],
                tags: [],
                statuses: [],
            } as TraceAggregatorTreeContent,
            servicesMap: {},
            dataLoading: false,
            selectedTrace: {} as TraceAggregatorDetail,
            selectedTraceTypes: new Array<string>(),
            selectedTraceTags: new Array<string>(),
            selectedTraceStatuses: new Array<string>(),
            selectedTraceServiceIds: new Array<number>(),
            traceTotalIndicatorsNumber: 0,
            traceIndicatingIds: []
        }
    },
    getters: {
        filteredTree(store: TraceAggregatorTreeStoreInterface) {
            return store.tree.filter(
                (node: TraceTreeNode) => {
                    return !node.isHiddenByFilter
                }
            )
        }
    },
    actions: {
        async initTree(traceId: string) {
            this.$reset()

            return this.findTreeNodes({
                    traceId: traceId,
                    fresh: false,
                    freshContent: true,
                    isChild: false,
                }
            )
        },
        async initTreeByRow(row: TraceTreeNode) {
            this.$reset()

            return this.findTreeNodes({
                traceId: row.primary.trace_id,
                fresh: false,
                freshContent: true,
                isChild: true,
            })
        },
        async updateTree() {
            return this.findTreeNodes({
                traceId: this.parameters.trace_id,
                fresh: false,
                freshContent: false,
                isChild: this.parameters.is_child,
            })
        },
        async freshTree() {
            const traceId = this.parameters.trace_id;
            const isChild = this.parameters.is_child;

            this.$reset()

            return this.findTreeNodes({
                traceId: traceId,
                fresh: true,
                freshContent: true,
                isChild: isChild,
            })
        },
        async findTreeNodes(
            {
                traceId,
                fresh,
                freshContent,
                isChild
            }: {
                traceId: string,
                fresh: boolean,
                freshContent: boolean,
                isChild: boolean
            }
        ) {
            this.loading = true

            this.parameters = {
                trace_id: traceId,
                fresh: fresh,
                is_child: isChild
            }

            return handleApiRequest(
                ApiContainer.get()
                    .traceAggregatorTracesTreeCreate(
                        this.parameters,
                        {
                            type: undefined,
                            format: undefined,
                        }
                    )
                    .then(response => {
                        readStream(response.body!)
                            .then(result => {
                                this.setTreeNodes(JSON.parse(result))

                                if (!freshContent) {
                                    this.loading = false
                                } else {
                                    handleApiRequest(
                                        this.findTreeContent(traceId, isChild)
                                            .finally(() => {
                                                this.loading = false
                                            })
                                    )
                                }
                            })
                            .catch((error) => {
                                handleApiError(error)

                                this.loading = false
                            })
                    })
                    .catch((error) => {
                        handleApiError(error)

                        this.loading = false
                    })
            )
        },
        findTreeContent(traceId: string, isChild: boolean) {
            const parameters: TraceAggregatorTreeContentParameters = {
                trace_id: traceId,
                is_child: isChild
            }

            return handleApiRequest(
                ApiContainer.get().traceAggregatorTracesTreeContentCreate(parameters)
                    .then(response => {
                        this.setTreeContent(response.data.data)
                    }))
        },
        async findData(traceId: string) {
            if (traceId === this.selectedTrace.trace_id) {
                this.resetSelectedTrace()

                return
            }

            this.dataLoading = true

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesDetail(traceId)
                    .then(response => {
                        // @ts-ignore TODO
                        this.selectedTrace = response.data
                    })
                    .finally(() => {
                        this.dataLoading = false
                    })
            )
        },
        resetSelectedTrace() {
            this.selectedTrace = {} as TraceAggregatorDetail
        },
        setTreeNodes(tree: TraceAggregatorTree) {
            this.treeNodes = tree

            this.tree = new TreeBuilder().build(this.treeNodes)
        },
        setTreeContent(content: TraceAggregatorTreeContent) {
            this.content = content

            this.servicesMap = {}

            this.content.services.forEach((service: TraceAggregatorTreeContentService) => {
                this.servicesMap[service.id] = service
            })
        },
        applyFilters() {
            new TreeFilter(
                this.selectedTraceServiceIds,
                this.selectedTraceTypes,
                this.selectedTraceTags,
                this.selectedTraceStatuses,
            ).apply(this.tree)
        }
    },
})
