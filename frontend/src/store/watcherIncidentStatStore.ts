import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../utils/handleApiRequest.ts";
import {currentSession, sessionEnded} from "./session.ts";

export type WatcherIncidentStat = AdminApi.WatchersIncidentsStatList.ResponseBody['data']

interface WatcherIncidentStatStoreInterface {
    openedCount: number
}

/**
 * How many incidents stand open, read when somebody asks for it.
 *
 * Nothing here follows the incidents on its own: no subscription and no timer. The number
 * is refreshed where the panel already knows it moved — the header on load, closing an
 * incident, deleting a watcher — and by the Refresh button beside the list.
 */
export const useWatcherIncidentStatStore = defineStore('watcherIncidentStatStore', {
    state: (): WatcherIncidentStatStoreInterface => {
        return {
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
                        // otherwise show the last person's number.
                        if (sessionEnded(session)) {
                            return
                        }

                        this.openedCount = response.data.data.opened_count
                    })
            )
        },
    },
})
