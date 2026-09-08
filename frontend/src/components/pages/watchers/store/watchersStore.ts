import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";
import {useIncidentsStore} from "./incidentsStore.ts";
import {useWatcherIncidentStatStore} from "../../../../store/watcherIncidentStatStore.ts";

export type Watcher = AdminApi.WatchersList.ResponseBody['data'][number];

/** What every type's create and edit body looks like above its settings. */
export type WatcherPayload = AdminApi.WatchersSlowTracesCreate.RequestBody

/**
 * The settings of one watcher, read back.
 *
 * Every type's fields in one shape, the ones that do not apply simply absent. The server
 * answers a shape per type — the route names it — but the form that shows them is built
 * from `/watchers/types` at runtime, so there is nothing on this side to match a narrower
 * type against.
 */
export type WatcherSettings = {
    id: number,
    threshold?: number,
    period_minutes?: number,
    window_minutes?: number,
    baseline_minutes?: number,
    growth_percent?: number,
    duration?: number,
    filter?: {
        service_ids?: number[],
        types?: string[],
        tags?: string[],
    },
}

/**
 * Which endpoints answer for a type.
 *
 * Settings are read and written one type at a time, each on its own route, so that a
 * misspelt field is a 422 rather than a default nobody chose. The price is paid here: the
 * generated client has a triple of methods per type and no way to pick one by name.
 *
 * The bodies are cast because the generator gives each type's method a body type of its
 * own, while the form building them is driven by `/watchers/types` and knows only field
 * keys. The server is what checks them, which is the whole point of a route per type.
 */
const typeEndpoints: Record<string, {
    show: (id: number) => Promise<{data: {data: WatcherSettings}}>,
    create: (payload: WatcherPayload) => Promise<unknown>,
    update: (id: number, payload: WatcherPayload) => Promise<unknown>,
}> = {
    bufferOverflow: {
        show: id => ApiContainer.get().watchersBufferOverflowDetail(id),
        create: payload => ApiContainer.get().watchersBufferOverflowCreate(payload as any),
        update: (id, payload) => ApiContainer.get().watchersBufferOverflowPartialUpdate(id, payload as any),
    },
    invalidBufferGrown: {
        show: id => ApiContainer.get().watchersInvalidBufferGrownDetail(id),
        create: payload => ApiContainer.get().watchersInvalidBufferGrownCreate(payload as any),
        update: (id, payload) => ApiContainer.get().watchersInvalidBufferGrownPartialUpdate(id, payload as any),
    },
    noNewTraces: {
        show: id => ApiContainer.get().watchersNoNewTracesDetail(id),
        create: payload => ApiContainer.get().watchersNoNewTracesCreate(payload as any),
        update: (id, payload) => ApiContainer.get().watchersNoNewTracesPartialUpdate(id, payload as any),
    },
    tracesSpike: {
        show: id => ApiContainer.get().watchersTracesSpikeDetail(id),
        create: payload => ApiContainer.get().watchersTracesSpikeCreate(payload as any),
        update: (id, payload) => ApiContainer.get().watchersTracesSpikePartialUpdate(id, payload as any),
    },
    slowTraces: {
        show: id => ApiContainer.get().watchersSlowTracesDetail(id),
        create: payload => ApiContainer.get().watchersSlowTracesCreate(payload as any),
        update: (id, payload) => ApiContainer.get().watchersSlowTracesPartialUpdate(id, payload as any),
    },
}

export function watcherTypeIsKnown(type: string): boolean {
    return type in typeEndpoints
}

interface WatchersStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<Watcher>
}

export const useWatchersStore = defineStore('watchersStore', {
    state: (): WatchersStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<Watcher>,
        }
    },
    actions: {
        async find() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().watchersList()
                    .then(response => {
                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        /**
         * The settings of one watcher, from the endpoint belonging to its type.
         *
         * A type the panel does not know about is a panel older than the server. Nothing
         * is asked for in that case — the form has no fields to show for it either.
         */
        async findSettings(type: string, id: number): Promise<WatcherSettings | null> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return null
            }

            return await handleApiRequest(
                () => endpoints.show(id).then(response => response.data.data)
            )
        },
        /**
         * Answers true only when the watcher was actually written.
         *
         * handleApiRequest reports a rejection and answers with undefined rather than
         * throwing, so this is how the dialog knows whether it may close.
         */
        async create(type: string, payload: WatcherPayload): Promise<boolean> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return false
            }

            return await handleApiRequest(
                () => endpoints.create(payload).then(() => this.find()).then(() => true)
            ) === true
        },
        async update(type: string, id: number, payload: WatcherPayload): Promise<boolean> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return false
            }

            return await handleApiRequest(
                () => endpoints.update(id, payload).then(() => this.find()).then(() => true)
            ) === true
        },
        async remove(id: number) {
            return await handleApiRequest(
                () => ApiContainer.get().watchersDelete(id)
                    .then(() => {
                        this.items = this.items.filter((watcher: Watcher) => watcher.id !== id)

                        this.forgetIncidentsOf(id)
                    })
            )
        },
        /**
         * A deleted watcher takes its incidents and their events with it — the foreign
         * keys do it in the database, and nothing tells the panel: no incident changed,
         * the rows simply went away, so no frame is published either.
         */
        forgetIncidentsOf(id: number) {
            const incidentsStore = useIncidentsStore()

            // A filter naming a watcher that no longer exists would answer with an empty
            // list for ever, and the select would show a bare id with no name behind it.
            if (incidentsStore.watcherId === id) {
                incidentsStore.watcherId = null
            }

            if (incidentsStore.loaded) {
                incidentsStore.applyFilter()
            }

            // The badge is on every page, so it is refreshed whether the list was open
            // or not.
            useWatcherIncidentStatStore().findStat()
        },
    },
})
