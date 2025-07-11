import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {readStream} from "../../../../../../utils/helpers.ts";
import {TreeBuilder} from "./TreeBuilder.ts";

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
                                this.setTreeNodes(JSON.parse(result))

                                if (!freshContent) {
                                    this.loading = false
                                } else {
                                    handleApiRequest(
                                        this.findTreeContent(traceId)
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

            this.tree = new TreeBuilder().build(this.treeNodes)
        },
        setTreeContent(content: TraceAggregatorTreeContent) {
            this.content = content

            this.servicesMap = {}

            this.content.services.forEach((service: TraceAggregatorTreeContentService) => {
                this.servicesMap[service.id] = service
            })
        },
    },
})
