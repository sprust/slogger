import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {TraceAggregatorService} from "../../services/store/traceAggregatorServicesStore.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

type TraceAggregatorTreeNodeParameters = AdminApi.TraceAggregatorTracesTreeDetail.RequestParams
export type TraceAggregatorTree = AdminApi.TraceAggregatorTracesTreeDetail.ResponseBody['data']
export type TraceAggregatorTreeNode = AdminApi.TraceAggregatorTracesTreeDetail.ResponseBody['data']['items'][number]

interface TraceAggregatorTreeStoreInterface {
    loading: boolean,
    parameters: TraceAggregatorTreeNodeParameters,
    tracesCount: number,
    treeNodes: Array<TraceAggregatorTreeNode>,
    dataLoading: boolean,
    selectedTrace: TraceAggregatorDetail,
    traceTypes: Array<string>,
    selectedTraceTypes: Array<string>,
    traceServices: Array<TraceAggregatorService>,
    selectedTraceServiceIds: Array<number>,
    traceTotalIndicatorsNumber: number,
    traceIndicatingIds: Array<string>,
}

export const useTraceAggregatorTreeStore = defineStore('traceAggregatorTreeStore', {
    state: (): TraceAggregatorTreeStoreInterface => {
        return {
            loading: false,
            parameters: {} as TraceAggregatorTreeNodeParameters,
            tracesCount: 0,
            treeNodes: new Array<TraceAggregatorTreeNode>,
            dataLoading: false,
            selectedTrace: {} as TraceAggregatorDetail,
            traceTypes: new Array<string>(),
            selectedTraceTypes: new Array<string>(),
            traceServices: new Array<TraceAggregatorService>(),
            selectedTraceServiceIds: new Array<number>(),
            traceTotalIndicatorsNumber: 0,
            traceIndicatingIds: []
        }
    },
    actions: {
        async findTreeNodes(traceId: string) {
            this.loading = true

            this.resetData()

            this.treeNodes = []

            this.parameters = {
                traceId: traceId
            }

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesTreeDetail(this.parameters.traceId)
                    .then(response => {
                        this.setTreeNodes(response.data.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async refreshTree() {
            this.loading = true

            this.resetData()

            this.treeNodes = []

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesTreeDetail(this.parameters.traceId)
                    .then(response => {
                        this.setTreeNodes(response.data.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findData(traceId: string) {
            if (traceId === this.selectedTrace.trace_id) {
                this.resetData()

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
        resetData() {
            this.selectedTrace = {} as TraceAggregatorDetail
        },
        setTreeNodes(tree: TraceAggregatorTree) {
            this.tracesCount = tree.tracesCount
            this.treeNodes = tree.items

            this.parseTree(this.treeNodes)
            this.calcTraceIndicators(this.treeNodes)
        },
        parseTree(treeNodes: Array<TraceAggregatorTreeNode>) {
            this.traceTypes = []
            this.selectedTraceTypes = []
            this.traceServices = []
            this.selectedTraceServiceIds = []

            this.parseTreeRecursive(treeNodes)
        },
        parseTreeRecursive(treeNodes: Array<TraceAggregatorTreeNode>) {
            treeNodes.forEach((treeNode: TraceAggregatorTreeNode) => {
                if (treeNode.service?.id
                    && !this.traceServices.find(
                        (service: TraceAggregatorService) => treeNode.service?.id === service.id
                    )
                ) {
                    this.traceServices.push(treeNode.service)
                }

                if (this.traceTypes.indexOf(treeNode.type) === -1) {
                    this.traceTypes.push(treeNode.type)
                }

                // @ts-ignore recursion
                this.parseTreeRecursive(treeNode.children)
            })
        },
        calcTraceIndicators(treeNodes: Array<TraceAggregatorTreeNode>) {
            this.traceTotalIndicatorsNumber = 0
            this.traceIndicatingIds = []

            this.calcTraceIndicatorsRecursive(treeNodes)
        },
        calcTraceIndicatorsRecursive(treeNodes: Array<TraceAggregatorTreeNode>) {
            treeNodes.forEach((treeNode: TraceAggregatorTreeNode) => {
                this.traceIndicatingIds.push(treeNode.trace_id)

                this.traceTotalIndicatorsNumber += (treeNode.duration ?? 0)

                // @ts-ignore recursion
                this.calcTraceIndicatorsRecursive(treeNode.children)
            })
        }
    },
})
