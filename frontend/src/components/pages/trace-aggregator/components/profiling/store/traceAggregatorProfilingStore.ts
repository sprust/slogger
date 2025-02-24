import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
// @ts-ignore todo
import {Node, Edge} from "@vue-flow/core/dist/types/node";
import {FlowBuilder} from "../utils/flowBuilder.ts";
import {IndicatorsCollector} from "../utils/indicatorsCollector.ts";
import {TreeBuilder} from "../utils/treeBuilder.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

type Parameters = AdminApi.TraceAggregatorTracesProfilingCreate.RequestParams
type Body = AdminApi.TraceAggregatorTracesProfilingCreate.RequestBody

export type Profiling = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']
export type ProfilingNode = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']['nodes'][number]
export type ProfilingNodeDataItem = AdminApi.TraceAggregatorTracesProfilingCreate.ResponseBody['data']['nodes'][number]['data'][number]

export interface FlowItems {
    nodes: Array<Node>,
    edges: Array<Edge>,
}

interface TraceAggregatorProfilingStoreInterface {
    loading: boolean,
    parameters: Parameters,
    requestBody: Body,
    profiling: Profiling,
    showExcludedCallerPreviewDialog: boolean,
    excludedCallerPreview: string,
    showTree: boolean,
    selectedItem: ProfilingNode | null,
    profilingIndicators: Array<string>,
    showProfilingIndicators: Array<string>
    treeTable: Array<ProfilingTreeNodeV2>,
    flowItems: FlowItems,
}

export type ProfilingTreeNode = {
    key: number,
    label: string,
    children: null | Array<ProfilingTreeNode>,
    primary: ProfilingNode,
}

export type ProfilingTreeNodeV2 = {
    dept: number,
    id: number,
    parentId: null | number,
    calling: string,
    data: Array<ProfilingNodeDataItem>,
    recursionNodeId: null | number,
    primary: ProfilingNode,
    hide: boolean,
}

export const useTraceAggregatorProfilingStore = defineStore('traceAggregatorProfilingStore', {
    state: (): TraceAggregatorProfilingStoreInterface => {
        return {
            loading: true,
            parameters: {} as Parameters,
            requestBody: {
                caller: null,
                excluded_callers: []
            } as Body,
            profiling: {
                nodes: []
            },
            showExcludedCallerPreviewDialog: false,
            excludedCallerPreview: '',
            showTree: true,
            selectedItem: null as ProfilingNode | null,
            profilingIndicators: [],
            showProfilingIndicators: [],
            treeTable: new Array<ProfilingTreeNodeV2>(),
            flowItems: {
                nodes: [],
                edges: [],
            },
        }
    },
    actions: {
        async findProfiling(traceId: string) {
            this.loading = true

            this.profiling = {
                nodes: []
            }

            this.parameters = {
                traceId: traceId
            }

            this.requestBody = {
                caller: null,
                excluded_callers: []
            } as Body

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesProfilingCreate(traceId, {})
                    .then(response => {
                        this.setProfiling(response.data.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findProfilingWithBody() {
            this.loading = true

            this.profiling = {
                nodes: []
            }

            return await handleApiRequest(
                ApiContainer.get()
                    .traceAggregatorTracesProfilingCreate(
                        this.parameters.traceId,
                        this.requestBody
                    )
                    .then(response => {
                        this.setProfiling(response.data.data)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        setProfiling(profiling: Profiling) {
            this.selectedItem = null
            this.flowItems = {
                nodes: [],
                edges: [],
            }

            this.profiling = profiling

            this.profilingIndicators = (new IndicatorsCollector()).collect(this.profiling.nodes)

            this.showProfilingIndicators = this.profilingIndicators.length
                ? [this.profilingIndicators[0]]
                : []

            this.treeTable = (new TreeBuilder(this.profiling.nodes)).build()
        },
        setSelectedProfilingItem(item: ProfilingNode | null) {
            if (!item) {
                this.selectedItem = null
                this.flowItems = {
                    nodes: [],
                    edges: [],
                }

                return;
            }

            this.selectedItem = item

            const flow = (new FlowBuilder(this.selectedItem)).build()

            this.flowItems.nodes = flow.nodes
            this.flowItems.edges = flow.edges
        },
        switchShowTree() {
            this.showTree = !this.showTree
        },
        setBodyCaller(caller: string) {
            this.requestBody.caller = caller
        },
        addBodyExcludedCallers(excludedCaller: string) {
            this.requestBody.excluded_callers!.push(excludedCaller)
        },
        deleteBodyExcludedCallers(excludedCaller: string) {
            this.requestBody.excluded_callers = this.requestBody.excluded_callers!.filter(
                item => item !== excludedCaller
            )
        },

    },
})
