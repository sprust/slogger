import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {TraceAggregatorService} from "../../services/store/traceAggregatorServicesStore.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

type TraceAggregatorTreeNodeParameters = AdminApi.TraceAggregatorTracesTreeCreate.RequestBody
export type TraceAggregatorTree = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data']
export type TraceAggregatorTreeRow = AdminApi.TraceAggregatorTracesTreeCreate.ResponseBody['data']['items'][number]

interface TraceAggregatorTreeStoreInterface {
    loading: boolean,
    parameters: TraceAggregatorTreeNodeParameters,
    tracesCount: number,
    treeNodes: Array<TraceAggregatorTreeRow>,
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
            treeNodes: new Array<TraceAggregatorTreeRow>,
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
                trace_id: traceId,
                fresh: false, // TODO
                page: 1
            }

            return await handleApiRequest(
                ApiContainer.get()
                    .traceAggregatorTracesTreeCreate(this.parameters)
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
                ApiContainer.get().traceAggregatorTracesTreeCreate(this.parameters)
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
            this.tracesCount = tree.count
            this.treeNodes = tree.items
        },
        parseTreeRecursive(treeNodes: Array<TraceAggregatorTreeRow>) {
            treeNodes.forEach((treeNode: TraceAggregatorTreeRow) => {
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
