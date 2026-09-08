import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi, WatchersIncidentsListParamsStatusEnum} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {useWatcherIncidentStatStore} from "../../../../store/watcherIncidentStatStore.ts";

export type WatcherIncident = AdminApi.WatchersIncidentsList.ResponseBody['data'][number];
export type WatcherIncidentEvent = AdminApi.WatchersIncidentsEventsList.ResponseBody['data'][number];
export type WatcherIncidentEventGroup = WatcherIncidentEvent['payload']['groups'][number];

const perPage = 50

/** Events per incident, kept for as long as the page is open: an incident's history does
 * not change once it is read, except by a frame that reloads the row anyway. */
interface EventsByIncident {
    [incidentId: number]: Array<WatcherIncidentEvent>
}

interface IncidentsStoreInterface {
    loading: boolean
    loaded: boolean
    page: number
    status: WatchersIncidentsListParamsStatusEnum | null
    watcherId: number | null
    items: Array<WatcherIncident>
    events: EventsByIncident
    loadingEvents: { [incidentId: number]: boolean }
}

export const useIncidentsStore = defineStore('incidentsStore', {
    state: (): IncidentsStoreInterface => {
        return {
            loading: false,
            loaded: false,
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

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsList(query)
                    .then(response => {
                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findEvents(incidentId: number) {
            this.loadingEvents[incidentId] = true

            return await handleApiRequest(
                () => ApiContainer.get().watchersIncidentsEventsList(incidentId)
                    .then(response => {
                        this.events[incidentId] = response.data.data
                    })
                    .finally(() => {
                        delete this.loadingEvents[incidentId]
                    })
            )
        },
        async close(incidentId: number) {
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
        setPage(page: number) {
            this.page = page

            return this.find()
        },
        /** The filter has been changed — whoever changed it wrote it here first. */
        applyFilter() {
            this.page = 1

            return this.find()
        },
        /** An incident moved: whatever is on screen is a page old. */
        reload(incidentId: number) {
            if (this.events[incidentId]) {
                this.findEvents(incidentId)
            }

            return this.find()
        },
    },
})
