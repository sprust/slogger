import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer, ApiTokenStorage} from "../utils/apiContainer.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../utils/handleApiRequest.ts";
import {useSconcurStore} from "../components/pages/sconcur/store/sconcurStore.ts";
import {EchoContainer} from "../utils/echoContainer.ts";
import {
    useTraceAggregatorTreeStore
} from "../components/pages/trace-aggregator/components/tree/store/traceAggregatorTreeStore.ts";
import {
    useTraceDynamicIndexesStore
} from "../components/pages/trace-aggregator/components/dynamic-indexes/store/traceDynamicIndexesStore.ts";
import {useWatcherIncidentStatStore} from "./watcherIncidentStatStore.ts";
import {useIncidentsStore} from "../components/pages/watchers/store/incidentsStore.ts";
import {useWatchersStore} from "../components/pages/watchers/store/watchersStore.ts";
import {useChannelsStore} from "../components/pages/watchers/components/notifications/store/channelsStore.ts";
import {useChannelTypesStore} from "../components/pages/watchers/components/notifications/store/channelTypesStore.ts";
import {useDeliveriesStore} from "../components/pages/watchers/components/notifications/store/deliveriesStore.ts";
import {useWatcherTypesStore} from "../components/pages/watchers/store/watcherTypesStore.ts";
import {nextSession} from "./session.ts";

type AuthUser = AdminApi.AuthMeList.ResponseBody['data']

interface AuthStoreInterface {
    user: AuthUser | null
}

export const useAuthStore = defineStore('authStore', {
    state: (): AuthStoreInterface => {
        return {
            user: null
        }
    },
    actions: {
        async login(email: string, password: string) {
            return await handleApiRequest(
                () => ApiContainer.get()
                    .authLoginCreate({
                        email: email,
                        password: password
                    })
                    .then((response) => {
                        this.setUser(response.data.data)
                    })
            )
        },
        async auth() {
            if (!ApiTokenStorage.getToken()) {
                this.setUser(null)

                return
            }

            try {
                const response = await ApiContainer.get().authMeList()

                this.setUser(response.data.data)
            } catch (error: any) {
                if (error?.status === 401) {
                    // The session ended elsewhere. Same teardown as a logout: without it
                    // the tab keeps its already-signed subscriptions and goes on receiving
                    // pushed frames, with nothing left polling to notice.
                    this.endSession()

                    return
                }

                handleApiError(error)
            }
        },
        async logout() {
            // Told to the server first, while the token is still here to say it with.
            // Called directly rather than through handleApiRequest: that one answers a 401
            // by calling this, and a session the server has already dropped would loop.
            if (ApiTokenStorage.getToken()) {
                try {
                    await ApiContainer.get().authLogoutCreate()
                } catch {
                    // Already gone, or unreachable. The local half happens regardless —
                    // the alternative is a panel that cannot sign out while the API is
                    // down.
                }
            }

            this.endSession()
        },
        /**
         * Everything a tab has to let go of when its session is over, however it ended —
         * the button, a 401 from anywhere, the router guard.
         */
        endSession() {
            // The Sconcur page's polling loop and its history live in a store of their own
            // so they survive navigation, so this is where they stop.
            useSconcurStore().reset()

            // Before the client is dropped, not after: connect() refuses without a token,
            // and that is what stops anything still in flight — a watchStats() awaiting
            // its first read, say — from rebuilding the client for the session that has
            // just ended.
            this.setUser(null)

            // The client belongs to the session: its subscriptions were signed for the
            // person leaving, and whoever signs in next on this tab would inherit them.
            EchoContainer.disconnect()

            // Disconnecting takes every subscription with it, but not the polls that
            // stand in for them when there is no ws pool.
            useTraceAggregatorTreeStore().stopWatching()
            useTraceDynamicIndexesStore().stopWatchingStats()
            useWatcherIncidentStatStore().stopWatching()

            // Incidents, watchers, the types they come in and the number in the badge are
            // read once per session and held; the next person to sign in on this tab would
            // otherwise be shown what the last one was looking at until the first request
            // answers.
            useWatcherIncidentStatStore().$reset()
            useIncidentsStore().$reset()
            useWatchersStore().$reset()
            useWatcherTypesStore().$reset()
            useChannelsStore().$reset()
            useChannelTypesStore().$reset()
            useDeliveriesStore().$reset()

            // After the stores are cleared, not before: from here on an answer to a
            // request sent in the session that just ended is not written anywhere. Those
            // requests are not aborted and answer normally — the token was valid when
            // they went out — so without this they would refill the stores a moment after
            // they were emptied, `loaded` and all.
            nextSession()
        },
        setUser(user: AuthUser | null) {
            this.user = user

            if (user === null) {
                ApiTokenStorage.forgetToken()
            } else {
                ApiTokenStorage.setToken(user.api_token)

                EchoContainer.connect()
            }
        }
    },
})
