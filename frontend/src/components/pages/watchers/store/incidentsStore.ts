import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi, WatchersIncidentsListParamsStatusEnum} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {useWatcherIncidentStatStore} from "../../../../store/watcherIncidentStatStore.ts";
import {currentSession, sessionEnded} from "../../../../store/session.ts";

export type WatcherIncident = AdminApi.WatchersIncidentsList.ResponseBody['data'][number];

export type BufferOverflowEvent = AdminApi.WatchersIncidentsEventsBufferOverflowList.ResponseBody['data'][number];
export type InvalidBufferGrownEvent = AdminApi.WatchersIncidentsEventsInvalidBufferGrownList.ResponseBody['data'][number];
export type NoNewTracesEvent = AdminApi.WatchersIncidentsEventsNoNewTracesList.ResponseBody['data'][number];
export type ManyTracesEvent = AdminApi.WatchersIncidentsEventsManyTracesList.ResponseBody['data'][number];
export type SlowTracesEvent = AdminApi.WatchersIncidentsEventsSlowTracesList.ResponseBody['data'][number];

/**
 * One event of whichever watcher an incident belongs to.
 *
 * Every type answers on a route of its own, so each of these carries exactly the numbers
 * its watcher reports. Which one a given incident holds is decided by the watcher, not by
 * the event: the type comes from the watchers store.
 */
export type WatcherIncidentEvent =
    | BufferOverflowEvent
    | InvalidBufferGrownEvent
    | NoNewTracesEvent
    | ManyTracesEvent
    | SlowTracesEvent;

export type WatcherIncidentEventGroup = NonNullable<SlowTracesEvent['payload']>['groups'][number];

type EventQuery = { page: number, per_page: number }

/**
 * Which endpoint answers for a type.
 *
 * The same shape as the settings endpoints in watchersStore, and for the same reason: the
 * payload follows the watcher's type, and a route per type is what lets the answer name
 * its numbers instead of offering every number any type might report.
 */
const eventEndpoints: Record<
    string,
    (incidentId: string, query: EventQuery) => Promise<{ data: { data: Array<WatcherIncidentEvent> } }>
> = {
    bufferOverflow: (id, query) => ApiContainer.get().watchersIncidentsEventsBufferOverflowList(id, query),
    invalidBufferGrown: (id, query) => ApiContainer.get().watchersIncidentsEventsInvalidBufferGrownList(id, query),
    noNewTraces: (id, query) => ApiContainer.get().watchersIncidentsEventsNoNewTracesList(id, query),
    manyTraces: (id, query) => ApiContainer.get().watchersIncidentsEventsManyTracesList(id, query),
    slowTraces: (id, query) => ApiContainer.get().watchersIncidentsEventsSlowTracesList(id, query),
}

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

/** Events per incident, kept until the list is read again: reading it is what a refresh
 * of the table means, and an incident open on screen has to show what it holds now. */
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
    /** Which rows are open, so that leaving the page and coming back keeps them open. */
    expandedIncidentIds: Array<string>
    expandedEventIds: { [incidentId: string]: Array<string> }
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
            expandedIncidentIds: [] as Array<string>,
            expandedEventIds: {},
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
            this.reset()

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

            const session = currentSession()

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsList(query)
                    .then(response => {
                        // A later request has already been sent, so this answer describes
                        // a filter or a page nobody is looking at any more. Or the session
                        // it was sent in has ended, and it describes the last person's.
                        if (request !== findRequest || sessionEnded(session)) {
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
        async findEvents(incidentId: string, watcherType: string) {
            this.eventsPage[incidentId] = 1
            this.eventsExhausted[incidentId] = false

            return await this.loadEvents(incidentId, watcherType, 1, false)
        },
        /**
         * The next page, appended.
         *
         * An incident open for a day holds a few hundred events — 288 at the shortest
         * cooldown the form allows — and the first page is not all of them.
         */
        async findMoreEvents(incidentId: string, watcherType: string) {
            const page = (this.eventsPage[incidentId] ?? 1) + 1

            const loaded = await this.loadEvents(incidentId, watcherType, page, true)

            // Only once it is actually in hand. Counting the page as read whatever
            // happened would make the next press ask for the one after it, and the events
            // in between would never be shown.
            if (loaded === true) {
                this.eventsPage[incidentId] = page
            }

            return loaded
        },
        /**
         * A type the panel does not know about is a panel older than the server: there is
         * no endpoint to ask and no columns to show, so nothing is asked for.
         */
        async loadEvents(incidentId: string, watcherType: string, page: number, append: boolean) {
            const endpoint = eventEndpoints[watcherType]

            if (!endpoint) {
                this.eventsExhausted[incidentId] = true

                return false
            }

            this.loadingEvents[incidentId] = true

            const session = currentSession()

            return await handleApiRequest(
                () => endpoint(incidentId, {
                    page,
                    per_page: eventsPerPage,
                })
                    .then(response => {
                        if (sessionEnded(session)) {
                            return
                        }

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
        /**
         * Everything the table holds, dropped before the list is asked for again.
         *
         * A refresh that kept it was not a refresh: the rows left open stayed open over
         * their old events, so an incident that had spoken again since showed the same
         * page it showed a minute ago, and the reader had to collapse the row and open it
         * to see anything new. The rows are read from `items` and everything else here is
         * keyed by an incident id, so all of it belongs to the list that is being
         * replaced.
         */
        reset() {
            this.items = []
            this.expandedIncidentIds = []
            this.expandedEventIds = {}
            this.events = {}
            this.eventsPage = {}
            this.eventsExhausted = {}
            this.loadingEvents = {}
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
