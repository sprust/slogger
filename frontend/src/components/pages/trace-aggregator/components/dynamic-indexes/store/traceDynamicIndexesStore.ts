import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {EchoContainer} from "../../../../../../utils/echoContainer.ts";

export type TraceDynamicIndex = AdminApi.TraceAggregatorDynamicIndexesList.ResponseBody['data'][number];
export type TraceDynamicIndexStats = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']
export type TraceDynamicIndexInfo = AdminApi.TraceAggregatorDynamicIndexesStatsList.ResponseBody['data']['indexes_in_process'][number]

/**
 * The live stats subscription, and the timer of the poll that stands in when there is no
 * ws pool to subscribe to. Both are module-level: a function and a timer id are not state
 * to be reset, and there is only ever one of each.
 */
let unsubscribeStats: null | (() => void) = null
let statsPollTimeoutId: null | number = null

interface TraceDynamicIndexesStoreInterface {
    started: boolean,
    loading: boolean
    traceDynamicIndexStats: TraceDynamicIndexStats,
    traceDynamicIndexes: Array<TraceDynamicIndex>
}

export const useTraceDynamicIndexesStore = defineStore('traceDynamicIndexesStore', {
    state: (): TraceDynamicIndexesStoreInterface => {
        return {
            started: false,
            loading: false,
            traceDynamicIndexStats: {} as TraceDynamicIndexStats,
            traceDynamicIndexes: [] as Array<TraceDynamicIndex>
        }
    },
    actions: {
        async findTraceDynamicIndexes() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorDynamicIndexesList()
                    .then(response => {
                        this.traceDynamicIndexes = response.data.data
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findTraceDynamicIndexStats() {
            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorDynamicIndexesStatsList()
                    .then(response => {
                        this.traceDynamicIndexStats = response.data.data
                    })
            )
        },
        /**
         * Starts following what the indexes are doing, once per session.
         *
         * The snapshot is published by the task pool that does the building, so nobody
         * here has to ask for it — one reading serves every open tab, and silence means
         * there is nothing to build. The first read is still ours: until something is
         * being built, there is nothing to publish.
         *
         * Anything published before the channel goes live is missed, but this snapshot
         * repairs itself: the publisher repeats it every second while a build lasts, and
         * closes with one saying there is nothing. Only a build that both starts and
         * finishes inside the subscription handshake goes unseen, and that one had
         * nothing to show.
         *
         * Without a ws pool this falls back to the poll it replaced.
         */
        async watchStats() {
            if (this.started) {
                return
            }

            this.started = true

            await this.findTraceDynamicIndexStats()

            if (this.subscribeStats()) {
                return
            }

            this.pollStats()
        },
        /**
         * Subscribes if there is a socket to subscribe on, and says whether there was.
         *
         * Called again from every poll tick, which is what puts this back on the socket
         * after one is lost and comes back — the container answers null in between.
         */
        subscribeStats(): boolean {
            if (unsubscribeStats !== null) {
                return true
            }

            unsubscribeStats = EchoContainer.listen(
                'sl-trace-indexes',
                '.stats.updated',
                (stats: TraceDynamicIndexStats) => {
                    this.traceDynamicIndexStats = stats
                },
                {
                    // The socket, or the channel, turned out not to be there after all.
                    onLost: () => {
                        unsubscribeStats = null

                        this.pollStats()
                    },
                }
            )

            return unsubscribeStats !== null
        },
        stopWatchingStats() {
            this.started = false

            if (unsubscribeStats !== null) {
                unsubscribeStats()
                unsubscribeStats = null
            }

            if (statsPollTimeoutId !== null) {
                window.clearTimeout(statsPollTimeoutId)
                statsPollTimeoutId = null
            }
        },
        pollStats() {
            // Checked here as well as inside the timer: the request before this one may
            // have been in flight when the session ended, and rescheduling then would
            // leave a timer behind that stopWatchingStats() has already given up on.
            if (!this.started) {
                return
            }

            statsPollTimeoutId = window.setTimeout(
                () => {
                    if (!this.started) {
                        return
                    }

                    // One read either way: after a subscription it is the confirming one,
                    // and from then on the frames carry it.
                    if (this.subscribeStats()) {
                        this.findTraceDynamicIndexStats()

                        return
                    }

                    this.findTraceDynamicIndexStats().finally(() => this.pollStats())
                },
                2000
            )
        },
        async deleteTraceDynamicIndex(id: string) {
            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorDynamicIndexesDelete(id)
                    .then(() => {
                        this.traceDynamicIndexes = this.traceDynamicIndexes.filter(
                            (index: TraceDynamicIndex) => index.id !== id
                        )

                        // The published snapshot only moves while something is being
                        // built, and a delete moves the totals beside it. Nothing would
                        // correct them until the next build, so they are re-read here.
                        this.findTraceDynamicIndexStats()
                    })
            )
        },
    },
})
