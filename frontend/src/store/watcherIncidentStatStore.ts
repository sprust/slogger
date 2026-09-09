import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../utils/handleApiRequest.ts";
import {EchoContainer} from "../utils/echoContainer.ts";
import {currentSession, sessionEnded} from "./session.ts";

export type WatcherIncidentStat = AdminApi.WatchersIncidentsStatList.ResponseBody['data']

/**
 * The `sl-watchers` frame, as WatcherIncidentBroadcast::broadcastWith() writes it.
 *
 * A ws payload never reaches the OpenAPI schema, so this is the one place the shape is
 * written down on this side. The count is the badge; the rest says which incident moved,
 * which is what tells a list that happens to be open to read itself again.
 */
export type WatcherIncidentFrame = {
    incident_id: string,
    watcher_id: number,
    status: string,
    opened_count: number,
}

/**
 * The live subscription and the timer of the poll that stands in when there is no ws pool
 * to subscribe to. Module-level: a function and a timer id are not state to be reset, and
 * there is only ever one of each.
 */
let unsubscribeIncidents: null | (() => void) = null
let pollTimeoutId: null | number = null

/**
 * How often the badge asks when it cannot be told.
 *
 * Far slower than the aggregator's stats poll: a watcher speaks at most once per cooldown,
 * the shortest of which is measured in minutes, and this request runs on every page.
 */
const pollInterval = 30000

/**
 * How often it asks anyway while subscribed.
 *
 * A frame missed — the socket down for a moment, the tab asleep, a subscription still
 * being made — leaves a number on screen that nothing will correct, because the next frame
 * only comes when a watcher next speaks. The badge is shown on every page, so it confirms
 * itself on a slow beat rather than standing wrong until a reload.
 */
const subscribedReadInterval = 120000

/** Called on every frame, by whoever is showing incidents at the time. */
const frameHandlers = new Set<(frame: WatcherIncidentFrame) => void>()

interface WatcherIncidentStatStoreInterface {
    started: boolean
    openedCount: number
}

export const useWatcherIncidentStatStore = defineStore('watcherIncidentStatStore', {
    state: (): WatcherIncidentStatStoreInterface => {
        return {
            started: false,
            openedCount: 0,
        }
    },
    actions: {
        async findStat() {
            const session = currentSession()

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsStatList()
                    .then(response => {
                        // Signed out while this was on the wire: the badge would
                        // otherwise show the last person's number until the next frame.
                        if (sessionEnded(session)) {
                            return
                        }

                        this.openedCount = response.data.data.opened_count
                    })
            )
        },
        /**
         * Starts following how much there is to deal with, once per session.
         *
         * The first read is ours whatever happens next: the bus keeps no history, and
         * until a watcher speaks there is no frame to learn the count from.
         */
        async watch() {
            if (this.started) {
                return
            }

            this.started = true

            await this.findStat()

            this.subscribe()

            this.poll()
        },
        /**
         * Subscribes if there is a socket to subscribe on, and says whether there was.
         *
         * Called again from every poll tick, which is what puts the badge back on the
         * socket after one is lost and comes back — the container answers null in
         * between.
         */
        subscribe(): boolean {
            if (unsubscribeIncidents !== null) {
                return true
            }

            unsubscribeIncidents = EchoContainer.listen(
                'sl-watchers',
                '.incident.changed',
                (frame: WatcherIncidentFrame) => {
                    this.openedCount = frame.opened_count

                    frameHandlers.forEach(handler => handler(frame))
                },
                {
                    onLost: () => {
                        // Released rather than dropped: a refused channel leaves the
                        // client standing, and forgetting the unsubscriber here would
                        // leave the listener counted for ever.
                        this.stopSubscribing()

                        this.poll()
                    },
                }
            )

            return unsubscribeIncidents !== null
        },
        stopWatching() {
            this.started = false

            this.stopSubscribing()

            if (pollTimeoutId !== null) {
                window.clearTimeout(pollTimeoutId)
                pollTimeoutId = null
            }
        },
        stopSubscribing() {
            if (unsubscribeIncidents === null) {
                return
            }

            unsubscribeIncidents()
            unsubscribeIncidents = null
        },
        poll() {
            // Checked here as well as inside the timer: the request before this one may
            // have been in flight when the session ended, and rescheduling then would
            // leave a timer behind that stopWatching() has already given up on.
            if (!this.started) {
                return
            }

            // Cleared first: a second failure while one is already scheduled would
            // otherwise leave a timer nobody holds the id of, polling for as long as the
            // tab is open — logout included.
            if (pollTimeoutId !== null) {
                window.clearTimeout(pollTimeoutId)
            }

            const subscribed = unsubscribeIncidents !== null

            pollTimeoutId = window.setTimeout(
                () => {
                    if (!this.started) {
                        return
                    }

                    this.subscribe()

                    this.findStat().finally(() => this.poll())
                },
                subscribed ? subscribedReadInterval : pollInterval
            )
        },
        /**
         * Follows the frames for as long as the returned function is not called.
         *
         * The incidents list uses this to reload itself when something moved. It is
         * offered by this store rather than taken from the container directly so that
         * there is one subscription to `sl-watchers` on the panel: the badge holds it on
         * every page anyway, and a page opening a second one would poll separately when
         * the pool is down.
         */
        onFrame(handler: (frame: WatcherIncidentFrame) => void): () => void {
            frameHandlers.add(handler)

            return () => {
                frameHandlers.delete(handler)
            }
        },
    },
})
