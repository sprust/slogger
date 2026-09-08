import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi, WatchersIncidentsListParamsStatusEnum} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {useWatcherIncidentStatStore} from "../../../../store/watcherIncidentStatStore.ts";

export type WatcherIncident = AdminApi.WatchersIncidentsList.ResponseBody['data'][number];
export type WatcherIncidentEvent = AdminApi.WatchersIncidentsEventsList.ResponseBody['data'][number];
export type WatcherIncidentEventGroup = WatcherIncidentEvent['payload']['groups'][number];

const perPage = 50

const eventsPerPage = 50

/**
 * Which list request is the current one.
 *
 * Frames arrive one per watcher that spoke, so a single minute pass can put several list
 * requests in flight at once; without this the table settles on whichever answers last,
 * which may be older than the page the reader has since turned to.
 */
let findRequest = 0

/** Events per incident, kept for as long as the page is open: an incident's history does
 * not change once it is read, except by a frame that reloads the row anyway. */
interface EventsByIncident {
    [incidentId: string]: Array<WatcherIncidentEvent>
}

interface IncidentsStoreInterface {
    loading: boolean
    loaded: boolean
    eventsPage: { [incidentId: string]: number }
    eventsExhausted: { [incidentId: string]: boolean }
    page: number
    status: WatchersIncidentsListParamsStatusEnum | null
    watcherId: number | null
    items: Array<WatcherIncident>
    events: EventsByIncident
    loadingEvents: { [incidentId: string]: boolean }
}

export const useIncidentsStore = defineStore('incidentsStore', {
    state: (): IncidentsStoreInterface => {
        return {
            loading: false,
            loaded: false,
            eventsPage: {},
            eventsExhausted: {},
            page: 1,
            // Opened by default: the list is a work queue, and what has been dealt with
            // is history one has to ask for.
            status: WatchersIncidentsListParamsStatusEnum.Opened as WatchersIncidentsListParamsStatusEnum | null,
            watcherId: null as number | null,
            items: [] as Array<WatcherIncident>,
            events: {} as EventsByIncident,
            loadingEvents: {},
        }
    },
    getters: {
        // No total comes back with the list — the answer is the rows and nothing else —
        // so a full page is the only sign that there may be another.
        hasNextPage(state): boolean {
            return state.items.length === perPage
        },
    },
    actions: {
        async find() {
            this.loading = true

            // A filter nobody set is left out of the query rather than sent as null: the
            // generated client drops only `undefined`, and a null reaches the server as
            // the string "null" — which is neither a status nor an integer, so the whole
            // list comes back 422.
            const query = {
                page: this.page,
                per_page: perPage,
                ...(this.status === null ? {} : {status: this.status}),
                ...(this.watcherId === null ? {} : {watcher_id: this.watcherId}),
            }

            const request = ++findRequest

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsList(query)
                    .then(response => {
                        // A later request has already been sent, so this answer describes
                        // a filter or a page nobody is looking at any more.
                        if (request !== findRequest) {
                            return false
                        }

                        this.items = response.data.data

                        this.loaded = true

                        return true
                    })
                    .finally(() => {
                        if (request === findRequest) {
                            this.loading = false
                        }
                    })
            )
        },
        async findEvents(incidentId: string) {
            this.eventsPage[incidentId] = 1
            this.eventsExhausted[incidentId] = false

            return await this.loadEvents(incidentId, 1, false)
        },
        /**
         * The same events the reader already has, read again.
         *
         * Not findEvents: that snaps back to the first page, and an incident somebody has
         * pressed "Show more" on three times would lose two hundred rows every time the
         * watcher speaks — which, for an incident that is still going, is often.
         */
        async refreshEvents(incidentId: string) {
            const pages = this.eventsPage[incidentId] ?? 1

            this.eventsExhausted[incidentId] = false

            for (let page = 1; page <= pages; page++) {
                const loaded = await this.loadEvents(incidentId, page, page > 1)

                if (loaded !== true) {
                    return loaded
                }
            }

            return true
        },
        /**
         * The next page, appended.
         *
         * An incident open for a day holds a few hundred events — 288 at the shortest
         * cooldown the form allows — and the first page is not all of them.
         */
        async findMoreEvents(incidentId: string) {
            const page = (this.eventsPage[incidentId] ?? 1) + 1

            const loaded = await this.loadEvents(incidentId, page, true)

            // Only once it is actually in hand. Counting the page as read whatever
            // happened would make the next press ask for the one after it, and the events
            // in between would never be shown.
            if (loaded === true) {
                this.eventsPage[incidentId] = page
            }

            return loaded
        },
        async loadEvents(incidentId: string, page: number, append: boolean) {
            this.loadingEvents[incidentId] = true

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsEventsList(incidentId, {
                    page,
                    per_page: eventsPerPage,
                })
                    .then(response => {
                        const events = response.data.data

                        this.events[incidentId] = append
                            ? [...(this.events[incidentId] ?? []), ...events]
                            : events

                        // A short page is the last one: the answer carries no total, and
                        // asking for one would count rows nobody is going to read.
                        this.eventsExhausted[incidentId] = events.length < eventsPerPage
                    })
                    .finally(() => {
                        delete this.loadingEvents[incidentId]
                    })
                    .then(() => true)
            )
        },
        async close(incidentId: string) {
            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsClosePartialUpdate(incidentId)
                    .then(() => {
                        // The badge follows the same change over the bus, but a panel with
                        // no ws pool would keep the old number until its next poll — and
                        // the person who just closed the incident is the one most likely
                        // to look at it.
                        useWatcherIncidentStatStore().findStat()

                        return this.find()
                    })
            )
        },
        async setPage(page: number) {
            const previous = this.page

            this.page = page

            const loaded = await this.find()

            // Only a request that failed puts the page back. A stale one answers false —
            // a later request is already in flight and owns the page now.
            if (loaded === undefined) {
                this.page = previous
            }

            return loaded
        },
        /** The filter has been changed — whoever changed it wrote it here first. */
        applyFilter() {
            this.page = 1

            return this.find()
        },
    },
})
