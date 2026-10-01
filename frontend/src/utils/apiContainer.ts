import {Api} from "../api-schema/admin-api-schema.ts";

export class ApiTokenStorage {
    constructor() {
        throw new Error("Forbidden!")
    }

    public static getToken(): string | null {
        return localStorage.getItem("bearerToken")
    }

    public static setToken(token: string): void {
        localStorage.setItem("bearerToken", token)
    }

    public static forgetToken(): void {
        localStorage.removeItem("bearerToken")
    }
}

/**
 * Aborts the requests of the session they were sent in once it ends.
 *
 * A request still in flight at a sign-out answers normally — the token was valid when it
 * left — and whatever it resolves writes the previous session's data back into a store
 * just emptied, or, for a poll, schedules the next round. Aborted, it rejects with an
 * AbortError instead, which handleApiError passes over in silence.
 */
let sessionAbort = new AbortController()

function sessionFetch(input: RequestInfo | URL, init?: RequestInit): Promise<Response> {
    return fetch(input, {...init, signal: anySignal(sessionAbort.signal, init?.signal)})
}

/** AbortSignal.any by hand: it is too recent for the browsers the panel still has to serve. */
function anySignal(session: AbortSignal, own?: AbortSignal | null): AbortSignal {
    if (!own) {
        return session
    }

    const combined = new AbortController()

    for (const signal of [session, own]) {
        if (signal.aborted) {
            combined.abort(signal.reason)

            return combined.signal
        }

        signal.addEventListener("abort", () => combined.abort(signal.reason), {once: true, signal: combined.signal})
    }

    return combined.signal
}

export class ApiContainer {
    constructor() {
        throw new Error("Forbidden!")
    }

    /** Aborts every request of the session that is ending; the next ones start clean. */
    public static abortSession(): void {
        sessionAbort.abort()

        sessionAbort = new AbortController()
    }

    public static get() {
        return this.client().adminApi
    }

    public static client() {
        return new Api({
            baseUrl: import.meta.env.VITE_BACKEND_URL,
            customFetch: sessionFetch,
            baseApiParams: {
                headers: {
                    "Accept": "application/json",
                    "Content-type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "Authorization": `Bearer ${ApiTokenStorage.getToken()}`,
                }
            }
        })
    }
}
