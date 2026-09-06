import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {TraceAggregatorDetail} from "../../trace/store/traceAggregatorDataStore.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {readStream} from "../../../../../../utils/helpers.ts";
import {TreeBuilder} from "./TreeBuilder.ts";
import {TreeFilter} from "./TreeFilter.ts";
import {IndicatorSetter} from "./IndicatorSetter.ts";
import {EchoContainer} from "../../../../../../utils/echoContainer.ts";

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

/**
 * Cancels the subscription to the state of the tree being watched.
 *
 * Module-level rather than store state: only one tree is watched at a time, and a
 * function in the state would be dropped by $reset() without ever being called.
 */
let unsubscribeTreeState: null | (() => void) = null

/**
 * The tree whose subscription went quiet long enough for the watchdog to fire.
 *
 * Kept so the fallback stays a fallback: without it every poll that comes back inProcess
 * subscribes again, waits out the watchdog again, and the tree refreshes once per
 * watchdogTimeout instead of once per second. Cleared by the first frame that does
 * arrive, and keyed by root trace id so another tree starts with a clean slate.
 */
let silentTreeId: null | string = null

/**
 * How long a subscription may deliver nothing before the poll takes over.
 *
 * Covers what a returned unsubscriber cannot promise: that the socket connected, that
 * nginx routed the upgrade, and that the channel was authorized.
 */
const watchdogTimeout = 10000

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
    building: boolean,
    treeTimeoutId: null | number,
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
            building: false,
            treeTimeoutId: null,
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
            this.stopWatching()
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
            this.stopWatching()
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
            this.stopWatching()
            this.$reset()

            return this.findTreeNodes({
                traceId: row.primary.trace_id,
                fresh: false,
                freshContent: true,
                isChild: true,
            })
        },
        async updateTree() {
            this.stopWatching()
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

            this.stopWatching()

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
                isChild,
                watch = true,
            }: {
                traceId: string,
                fresh: boolean,
                freshContent: boolean,
                isChild: boolean,
                // False when this call is itself a part of watching — the confirming read
                // right after subscribing, and the read a frame asks for. Without it the
                // two would call each other.
                watch?: boolean,
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

            const response = await handleApiRequest(async () => {
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
                    if (watch) {
                        this.watchState()
                    }

                    this.loading = false

                    return response
                }

                this.stopWatching()

                if (freshContent) {
                    await this.findTreeContent(traceId, isChild)
                }

                this.loading = false

                return response
            })

            if (!response) {
                this.stopWatching()
                this.loading = false
            }

            return response
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
        /**
         * Follows a build that is still running.
         *
         * The state is pushed on a channel of this tree's own. The poll below is what
         * happens when there is no ws pool to push it — the panel keeps working, it just
         * asks again the way it used to.
         */
        watchState() {
            this.building = true
            this.stopSubscription()
            this.clearTreeTimeout()

            // The root of the tree, which is not the trace that was opened: with
            // is_child false the server walks up to the topmost ancestor and builds
            // that (FindTraceTreeAction), and the state it broadcasts is keyed by it.
            // Subscribing by the opened trace id would mean a channel nobody publishes to.
            const rootTraceId = this.state?.root_trace_id

            if (!rootTraceId) {
                this.schedulePolling()

                return
            }

            // This tree's subscription has already been given its chance and delivered
            // nothing; re-subscribing would only spend another watchdog on it.
            if (silentTreeId === rootTraceId) {
                this.schedulePolling()

                return
            }

            const traceId = this.parameters.trace_id
            const isChild = this.parameters.is_child

            unsubscribeTreeState = EchoContainer.listen(
                `sl-trace-tree.${rootTraceId}`,
                '.state.changed',
                (state: TraceAggregatorTreeState) => this.onStateChanged(state),
                {
                    // Once the channel is actually listening, and not a moment before.
                    // The build was already running when this page asked to listen, and
                    // subscribing is a round trip of its own; a finish that landed in
                    // that window is gone for good, because the bus keeps no history. So
                    // the state is read once from here — after which nothing can be
                    // missed, since anything later arrives as a frame. The poll this
                    // replaces made one request per second; this makes one per build.
                    onSubscribed: () => {
                        this.findTreeNodes({
                            traceId: traceId,
                            fresh: false,
                            freshContent: true,
                            isChild: isChild,
                            watch: false,
                        })
                    },
                    onLost: () => {
                        // The socket turned out not to be there after all.
                        this.stopSubscription()

                        if (this.building) {
                            this.schedulePolling()
                        }
                    },
                }
            )

            if (!unsubscribeTreeState) {
                this.schedulePolling()

                return
            }

            // And if the subscription never completes at all — a pool that accepts the
            // socket and then says nothing — the poll takes over.
            this.armWatchdog()
        },
        /**
         * Falls back to polling if the subscription delivers nothing at all.
         *
         * Disarmed by the first frame — progress counts, so a build that is moving keeps
         * resetting it.
         */
        armWatchdog() {
            this.clearTreeTimeout()

            this.treeTimeoutId = window.setTimeout(
                () => {
                    if (!this.building) {
                        return
                    }

                    silentTreeId = this.state?.root_trace_id ?? null

                    this.stopSubscription()
                    this.schedulePolling()
                },
                watchdogTimeout
            )
        },
        onStateChanged(state: TraceAggregatorTreeState) {
            // A frame about a tree that is no longer the one on screen. The subscription
            // is dropped when the tree changes, but a frame already in flight still
            // arrives.
            if (state.root_trace_id !== this.state?.root_trace_id) {
                return
            }

            // The channel does deliver after all, whatever an earlier watchdog decided.
            silentTreeId = null

            this.armWatchdog()

            this.setTreeState(state)

            // Progress: the count moved, the build is still running.
            if (state.status === 'inProcess') {
                return
            }

            this.stopWatching()

            if (state.status !== 'finished') {
                return
            }

            // watch: true, not false — the read can still come back inProcess (a stale
            // state document, or a fresh build started in between), and then there has to
            // be something following it again.
            this.findTreeNodes({
                traceId: this.parameters.trace_id,
                fresh: false,
                freshContent: true,
                isChild: this.parameters.is_child,
            })
        },
        schedulePolling() {
            this.clearTreeTimeout()

            this.treeTimeoutId = window.setTimeout(async () => {
                if (!this.building) {
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
        /** Clears whichever of the two timers is pending — the poll or the watchdog. */
        clearTreeTimeout() {
            if (this.treeTimeoutId !== null) {
                window.clearTimeout(this.treeTimeoutId)
                this.treeTimeoutId = null
            }
        },
        stopSubscription() {
            if (unsubscribeTreeState !== null) {
                unsubscribeTreeState()
                unsubscribeTreeState = null
            }
        },
        stopWatching() {
            this.building = false

            // The latch lasts one build. A tree that is opened, or built, again deserves
            // the subscription retried — the pool may have been restarted since.
            silentTreeId = null

            this.stopSubscription()
            this.clearTreeTimeout()
        },
        async cancelBuild() {
            this.stopWatching()

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
