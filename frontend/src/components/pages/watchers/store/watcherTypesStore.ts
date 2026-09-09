import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {currentSession, sessionEnded} from "../../../../store/session.ts";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type WatcherType = AdminApi.WatchersTypesList.ResponseBody['data'][number];
export type WatcherTypeField = WatcherType['fields'][number];

interface WatcherTypesStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<WatcherType>
}

/**
 * What a watcher of each type can be configured with, as the server describes it.
 *
 * Read rather than written down here: the defaults live in the settings objects, and a
 * second copy in the panel would be the one that goes stale.
 */
export const useWatcherTypesStore = defineStore('watcherTypesStore', {
    state: (): WatcherTypesStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<WatcherType>,
        }
    },
    getters: {
        byType(state): (type: string) => WatcherType | undefined {
            return (type: string) => state.items.find((item: WatcherType) => item.type === type)
        },
        titleOf(state): (type: string) => string {
            return (type: string) => state.items.find((item: WatcherType) => item.type === type)?.title ?? type
        },
    },
    actions: {
        async find() {
            this.loading = true

            const session = currentSession()

            return await handleApiRequest(
                () => ApiContainer.get().watchersTypesList()
                    .then(response => {
                        // Signed out while this was on the wire: the answer describes a
                        // session that is over.
                        if (sessionEnded(session)) {
                            return
                        }

                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
    },
})
