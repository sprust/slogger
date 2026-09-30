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
    useTraceAggregatorGraphStore
} from "../components/pages/trace-aggregator/components/graph/store/traceAggregatorGraphStore.ts";
import {useLogsViewerStore} from "../components/pages/logs-viewer/store/logsViewerStore.ts";
import {nextSession} from "./session.ts";
import {resetSessionStores} from "./sessionStores.ts";

/** The sign-out under way, shared by everything that asks for one meanwhile. */
let loggingOut: Promise<void> | null = null

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
            // Several requests of an ended session answer 401 at once, and each of them
            // signs out: they share the one sign-out already under way rather than each
            // telling the server again.
            if (loggingOut === null) {
                loggingOut = this.signOut().finally(() => {
                    loggingOut = null
                })
            }

            return loggingOut
        },
        async signOut() {
            // Told to the server first, while the token is still here to say it with.
            // Called directly rather than through handleApiRequest: that one answers a 401
            // by calling logout(), and a session the server has already dropped would loop.
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
            // What keeps asking on its own is stopped first, by its own store: a store
            // reset below empties the state a loop runs on, not the timer that runs it.
            // The Sconcur page's polling, the tree build being followed, the log search
            // waiting for its files to be indexed, and the trace page's live graph, whose
            // next round would otherwise go out with no token and sign out again.
            useSconcurStore().reset()
            useTraceAggregatorTreeStore().stopWatching()
            useLogsViewerStore().stopRetry()
            useTraceAggregatorGraphStore().playGraph = false

            // Before the client is dropped, not after: connect() refuses without a token,
            // and that is what stops anything still in flight — a watchStats() awaiting
            // its first read, say — from rebuilding the client for the session that has
            // just ended.
            this.setUser(null)

            // What is already on its way is not waited for: it rejects as aborted, so its
            // answer neither refills a store emptied below nor schedules another round.
            ApiContainer.abortSession()

            // The client belongs to the session: its subscriptions were signed for the
            // person leaving, and whoever signs in next on this tab would inherit them.
            EchoContainer.disconnect()

            // Every store the tab has used, back to how a sign-in finds it: the filters,
            // the lists and the flags that say something is loaded. The next person to
            // sign in on this tab is shown nothing of what the last one was looking at.
            resetSessionStores([this.$id])

            // After the stores are cleared, not before: from here on an answer to a
            // request sent in the session that just ended is not written anywhere, should
            // one have left before the abort above could reach it.
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
