import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type SconcurStat = AdminApi.DashboardSconcurList.ResponseBody['data'];

/** Rolling window the chart draws and the tables average over: the last 15 minutes. */
export const WINDOW_MS = 900_000;

/** The whole master, as opposed to one of its groups or workers. */
export const MASTER_SOURCE = 'master';

/**
 * The master, a group or a worker. All three carry the same counter shape — the same
 * optional requests/consumers sections beside cpu, memory and goroutines — which is what
 * lets one sampler serve every source.
 */
export type StatRow = NonNullable<SconcurStat>
    | NonNullable<SconcurStat>['groups'][number]
    | NonNullable<SconcurStat>['workers'][number]

/**
 * One source's metrics at one moment.
 *
 * null is a gap in the series, not a zero: Chart.js skips such a point with spanGaps
 * false, which is how a section the master does not report reads as absent rather than
 * as an idle one.
 */
export type SourceMetrics = Record<string, number | null>

/** What every source read at one moment, so the chart can be re-pointed without a reload. */
export interface Sample {
    ts: number
    label: string
    values: Record<string, SourceMetrics>
}

/** The counters a rate is a difference of, kept per source between samples. */
interface Counters {
    completed: number | null
    handled: number | null
}

export function groupKey(name: string): string {
    return `group:${name}`
}

export function workerKey(pid: number): string {
    return `worker:${pid}`
}

interface SconcurStoreInterface {
    loading: boolean
    stat: SconcurStat | null
    history: Sample[]
    prevCounters: Record<string, Counters>
    prevAt: number | null
    selectedMetrics: string[]
    selectedSource: string
    autoUpdate: boolean
    timer: number | null
    ticking: boolean
}

/**
 * The collected history lives here rather than in the page, and so do the chart's
 * selections and its polling loop.
 *
 * The router unmounts a page when you leave it, so anything the component held would be
 * gone on the way back — and this page's whole point is a window of time that takes
 * fifteen minutes to fill. Keeping it in the store means switching to Logs and returning
 * finds the chart as it was, still filling if it was left running.
 */
export const useSconcurStore = defineStore('sconcur', {
    state: (): SconcurStoreInterface => {
        return {
            loading: false,
            stat: null,
            history: [],
            prevCounters: {},
            prevAt: null,
            selectedMetrics: ['rps', 'cpu_percent'],
            selectedSource: MASTER_SOURCE,
            autoUpdate: false,
            timer: null,
            ticking: false,
        }
    },
    actions: {
        async findSconcurStat() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().dashboardSconcurList()
                    .then(response => {
                        this.stat = response.data.data

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },

        /** Fetches a snapshot and records one sample of every source in it. */
        async tick() {
            // One at a time. The interval does not wait for the previous request, and
            // mounted() fires one of its own, so two could otherwise land milliseconds
            // apart and divide a whole second of work by that gap — a rate spike out of
            // nowhere every time the page is opened with the loop running.
            if (this.ticking) {
                return
            }

            this.ticking = true

            try {
                await this.collect()
            } finally {
                this.ticking = false
            }
        },

        async collect() {
            await this.findSconcurStat()

            const stat = this.stat

            if (!stat || !stat.available) {
                return
            }

            const now = Date.now()

            // Per second, and per *elapsed* second. Samples are not evenly spaced: auto
            // update is off by default, and a manual refresh after two idle minutes would
            // otherwise plot two minutes of work as one second of it.
            const elapsedSeconds = this.prevAt === null ? 0 : Math.max(0.001, (now - this.prevAt) / 1000)

            // Every source is sampled, not only the one on screen, so switching the filter
            // shows the history that was already there instead of starting from nothing.
            const counters: Record<string, Counters> = {}
            const values: Record<string, SourceMetrics> = {}

            values[MASTER_SOURCE] = this.sample(MASTER_SOURCE, stat, elapsedSeconds, counters)

            for (const group of stat.groups) {
                values[groupKey(group.name)] = this.sample(groupKey(group.name), group, elapsedSeconds, counters)
            }

            for (const worker of stat.workers) {
                values[workerKey(worker.pid)] = this.sample(workerKey(worker.pid), worker, elapsedSeconds, counters)
            }

            // Replaced rather than merged: a worker the master has retired takes its
            // counters with it, so a pid the kernel reuses cannot inherit a stranger's.
            this.prevCounters = counters
            this.prevAt = now

            this.history.push({
                ts: now,
                label: new Date(now).toLocaleTimeString(),
                values,
            })

            const cutoff = now - WINDOW_MS

            while (this.history.length > 0 && this.history[0].ts < cutoff) {
                this.history.shift()
            }

            // And a ceiling on the count, because the trim above trusts the clock: a
            // backwards correction would make the head never expire and the array grow
            // for the life of the tab.
            const maxSamples = Math.ceil(WINDOW_MS / 1000) + 1

            if (this.history.length > maxSamples) {
                this.history.splice(0, this.history.length - maxSamples)
            }
        },

        /**
         * One source's metrics at this moment, and its counters remembered for the next
         * sample. Both sections are optional — a pool serving no requests omits
         * `requests`, one consuming no queue omits `consumers` — and absent stays absent.
         */
        sample(key: string, row: StatRow, elapsedSeconds: number, counters: Record<string, Counters>): SourceMetrics {
            const requests = row.requests ?? null
            const consumers = row.consumers ?? null
            const previous = this.prevCounters[key]

            let rps: number | null = null

            if (requests && previous && previous.completed !== null && elapsedSeconds > 0) {
                rps = Math.max(0, requests.completed - previous.completed) / elapsedSeconds
            }

            let handled: number | null = null

            // Of `acked`, because that is what the tables and the header both call
            // "Handled". `delivered` counts a message the moment it reaches PHP, so a
            // queue whose jobs all fail would show throughput where nothing succeeded;
            // Refused stands beside it for those.
            if (consumers && previous && previous.handled !== null && elapsedSeconds > 0) {
                handled = Math.max(0, consumers.acked - previous.handled) / elapsedSeconds
            }

            counters[key] = {
                completed: requests ? requests.completed : null,
                handled: consumers ? consumers.acked : null,
            }

            return {
                // The counters go into the sample beside the averages: an average over the
                // window is the difference of two cumulative totals, not the mean of a
                // cumulative average.
                requests_completed: requests?.completed ?? null,
                consumers_handled: consumers?.acked ?? null,
                requests_in_flight: requests?.in_flight ?? null,
                rps: rps === null ? null : Math.round(rps * 100) / 100,
                cpu_percent: Math.round(row.cpu_percent * 10) / 10,
                memory_rss_mb: Math.round(row.memory_rss_bytes / 1048576),
                goroutines: row.goroutines,
                requests_avg_ms: requests ? Math.round(requests.avg_ms * 100) / 100 : null,
                consumers_in_flight: consumers?.in_flight ?? null,
                consumers_rate: handled === null ? null : Math.round(handled * 100) / 100,
                consumers_avg_ms: consumers ? Math.round(consumers.avg_ms * 100) / 100 : null,
            }
        },

        /**
         * Turns the one-second loop on or off, and keeps it running across navigation:
         * leaving the page with it on and coming back finds the window filled rather than
         * restarted. Never starts a second timer beside the first.
         */
        setAutoUpdate(value: boolean) {
            this.stopTimer()

            this.autoUpdate = value

            if (!value) {
                return
            }

            this.tick()

            this.timer = window.setInterval(() => this.tick(), 1000)
        },

        stopTimer() {
            if (this.timer !== null) {
                clearInterval(this.timer)
                this.timer = null
            }
        },

        /**
         * Stops the loop and drops what was collected.
         *
         * Called when the session ends, from wherever it ends — the button, a 401, the
         * router guard. The loop outlives navigation by design, so without this it would
         * outlive the session too and poll the login screen once a second with a token
         * that is gone; and the next person to sign in on the same tab would open the page
         * onto the previous one's fifteen minutes.
         */
        reset() {
            this.stopTimer()

            this.autoUpdate = false
            this.stat = null
            this.history = []
            this.prevCounters = {}
            this.prevAt = null
        },
    },
})
