import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {readStream} from "../../../../../../utils/helpers.ts";
import {TreeBuilder} from "./TreeBuilder.ts";
import {TreeFilter} from "./TreeFilter.ts";
import {IndicatorSetter} from "./IndicatorSetter.ts";

type TraceAggregatorTreeParameters = AdminApi.TraceAggregatorTracesTreeCreate.RequestBody
export type TraceAggregatorTreeRow = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data'][number]
export type TraceAggregatorTree = TraceAggregatorTreeRow[]
type TraceAggregatorTreeState = AdminApi.TraceAggregatorTracesTreeProcessesCancelPartialUpdate.ResponseBody['data']
type TraceAggregatorTreeStreamResponse = {
    state: TraceAggregatorTreeState,
    items?: TraceAggregatorTree,
}

type TraceAggregatorTreeContentParameters = AdminApi.TraceAggregatorTracesTreeContentCreate.RequestBody
type TraceAggregatorTreeContent = NonNullable<AdminApi.TraceAggregatorTracesTreeContentCreate.ResponseBody['data']['content']>
type TraceAggregatorTreeContentService = TraceAggregatorTreeContent['services'][number]

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
    indicatorPercent: number,
}

interface TraceAggregatorTreeStoreInterface {
    loading: boolean,
    polling: boolean,
    pollingTimeoutId: null | number,
    parameters: TraceAggregatorTreeParameters,
    state: null | TraceAggregatorTreeState,
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
            polling: false,
            pollingTimeoutId: null,
            parameters: {} as TraceAggregatorTreeParameters,
            state: null,
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
            const visibleNodes: TraceTreeNode[] = []
            let collapsedDepth: number | null = null

            store.tree.forEach((node: TraceTreeNode) => {
                if (collapsedDepth !== null) {
                    if (node.depth > collapsedDepth) {
                        return
                    }
                    collapsedDepth = null
                }

                if (node.isHiddenByFilter) {
                    return
                }

                visibleNodes.push(node)

                if (node.collapsed) {
                    collapsedDepth = node.depth
                }
            })

            return visibleNodes
        }
    },
    actions: {
        async initTreeParent(traceId: string) {
            this.stopPolling()
            this.$reset()

            return this.findTreeNodes({
                    traceId: traceId,
                    fresh: false,
                    freshContent: true,
                    isChild: false,
                }
            )
        },
        async initTreeCurrent(traceId: string) {
            this.stopPolling()
            this.$reset()

            return this.findTreeNodes({
                    traceId: traceId,
                    fresh: false,
                    freshContent: true,
                    isChild: true,
                }
            )
        },
        async initTreeByRow(row: TraceTreeNode) {
            this.stopPolling()
            this.$reset()

            return this.findTreeNodes({
                traceId: row.primary.trace_id,
                fresh: false,
                freshContent: true,
                isChild: true,
            })
        },
        async updateTree() {
            this.stopPolling()
            return this.findTreeNodes({
                traceId: this.parameters.trace_id,
                fresh: false,
                freshContent: false,
                isChild: this.parameters.is_child,
            })
        },
        async freshTree() {
            const traceId = this.parameters.trace_id
            const isChild = this.parameters.is_child

            this.stopPolling()

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
            this.treeNodes = []

            this.parameters = {
                trace_id: traceId,
                fresh: fresh,
                is_child: isChild
            }

            if (fresh) {
                this.content = {
                    count: 0,
                    services: [],
                    types: [],
                    tags: [],
                    statuses: [],
                }
            }

            return await handleApiRequest(async () => {
                const response = await ApiContainer.get()
                    .traceAggregatorTracesTreeCreate(
                        this.parameters,
                        {
                            type: undefined,
                            format: undefined,
                        }
                    )

                const result = await readStream(response.body!)
                const body = JSON.parse(result) as {data?: TraceAggregatorTreeStreamResponse} | TraceAggregatorTreeStreamResponse
                const data: TraceAggregatorTreeStreamResponse = 'data' in body
                    ? body.data as TraceAggregatorTreeStreamResponse
                    : body as TraceAggregatorTreeStreamResponse

                this.setTreeState(data.state)

                if (data.items) {
                    this.setTreeNodes(data.items)
                }

                if (data.state.status === 'inProcess') {
                    this.schedulePolling()
                    this.loading = false

                    return response
                }

                this.stopPolling()

                if (freshContent) {
                    await this.findTreeContent(traceId, isChild)
                }

                this.loading = false

                return response
            }).catch((error) => {
                handleApiError(error)
                this.stopPolling()
                this.loading = false

                return undefined
            })
        },
        findTreeContent(traceId: string, isChild: boolean) {
            const parameters: TraceAggregatorTreeContentParameters = {
                trace_id: traceId,
                is_child: isChild
            }

            return handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesTreeContentCreate(parameters)
                    .then(response => {
                        this.setTreeState(response.data.data.state)

                        if (response.data.data.content) {
                            this.setTreeContent(response.data.data.content)
                        }
                    }))
        },
        schedulePolling() {
            this.polling = true
            this.clearPollingTimeout()

            this.pollingTimeoutId = window.setTimeout(async () => {
                if (!this.polling) {
                    return
                }

                await this.findTreeNodes({
                    traceId: this.parameters.trace_id,
                    fresh: false,
                    freshContent: true,
                    isChild: this.parameters.is_child,
                })
            }, 1000)
        },
        clearPollingTimeout() {
            if (this.pollingTimeoutId !== null) {
                window.clearTimeout(this.pollingTimeoutId)
                this.pollingTimeoutId = null
            }
        },
        stopPolling() {
            this.polling = false
            this.clearPollingTimeout()
        },
        async cancelPolling() {
            this.stopPolling()

            if (!this.state?.root_trace_id) {
                return
            }

            this.loading = true
            const rootTraceId = this.state.root_trace_id

            return handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesTreeProcessesCancelPartialUpdate({
                    root_trace_id: rootTraceId,
                }).then(response => {
                    this.setTreeState(response.data.data)
                }).finally(() => {
                    this.loading = false
                })
            )
        },
        async findData(traceId: string) {
            if (traceId === this.selectedTrace.trace_id) {
                this.resetSelectedTrace()

                return
            }

            this.dataLoading = true

            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesDetail(traceId)
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
        setTreeState(state: TraceAggregatorTreeState) {
            this.state = state
        },
        setTreeNodes(tree: TraceAggregatorTree) {
            this.treeNodes = tree

            this.tree = new TreeBuilder().build(this.treeNodes)

            if (this.tree.length > 0) {
                this.fillTreeIndicatorsByRow(this.tree[0])
            }
        },
        fillTreeIndicatorsByRow(row: TraceTreeNode) {
            new IndicatorSetter(this.tree, row).fill()
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
        },
        toggleCollapse(row: TraceTreeNode) {
            row.collapsed = !row.collapsed
        }
    },
})
