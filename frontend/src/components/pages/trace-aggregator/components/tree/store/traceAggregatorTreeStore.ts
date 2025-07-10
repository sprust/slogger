import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {readStream} from "../../../../../../utils/helpers.ts";

type TraceAggregatorTreeParameters = AdminApi.TraceAggregatorTracesTreeCreate.RequestBody
export type TraceAggregatorTree = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data']
export type TraceAggregatorTreeRow = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data'][number]

type TraceAggregatorTreeContentParameters = AdminApi.TraceAggregatorTracesTreeContentCreate.RequestBody
type TraceAggregatorTreeContent = AdminApi.TraceAggregatorTracesTreeContentCreate.ResponseBody['data']
type TraceAggregatorTreeContentService = AdminApi.TraceAggregatorTracesTreeContentCreate.ResponseBody['data']['services'][number]

interface ServicesMapInterface {
    [key: number]: TraceAggregatorTreeContentService
}

interface TraceAggregatorTreeStoreInterface {
    loading: boolean,
    parameters: TraceAggregatorTreeParameters,
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
    actions: {
        async initTree(traceId: string) {
            this.$reset()

            return this.findTreeNodes(traceId, false, true)
        },
        async updateTree() {
            return this.findTreeNodes(this.parameters.trace_id, false, false)
        },
        async freshTree() {
            const traceId = this.parameters.trace_id;

            this.$reset()

            return this.findTreeNodes(traceId, true, true)
        },
        async findTreeNodes(traceId: string, fresh: boolean, freshContent: boolean) {
            this.loading = true

            this.parameters = {
                trace_id: traceId,
                fresh: fresh,
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
                                if (!freshContent) {
                                    this.loading = false
                                } else {
                                    handleApiRequest(
                                        this.findTreeContent(traceId)
                                            .then(() => {
                                                this.setTreeNodes(JSON.parse(result))
                                            })
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
                    .catch(() => {
                        this.loading = false
                    })
            )
        },
        findTreeContent(traceId: string) {
            const parameters: TraceAggregatorTreeContentParameters = {
                trace_id: traceId
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
        },
        setTreeContent(content: TraceAggregatorTreeContent) {
            this.content = content

            this.servicesMap = {}

            this.content.services.forEach((service: TraceAggregatorTreeContentService) => {
                this.servicesMap[service.id] = service
            })
        },
        calcTraceIndicators(treeNodes: Array<TraceAggregatorTreeRow>) {
            this.traceTotalIndicatorsNumber = 0
            this.traceIndicatingIds = []

            this.calcTraceIndicatorsRecursive(treeNodes)
        },
        calcTraceIndicatorsRecursive(treeNodes: Array<TraceAggregatorTreeRow>) {
            treeNodes.forEach((treeNode: TraceAggregatorTreeRow) => {
                this.traceIndicatingIds.push(treeNode.trace_id)

                this.traceTotalIndicatorsNumber += (treeNode.duration ?? 0)

                // @ts-ignore recursion
                this.calcTraceIndicatorsRecursive(treeNode.children)
            })
        }
    },
})
