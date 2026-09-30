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
    const signals = [sessionAbort.signal]

    if (init?.signal) {
        signals.push(init.signal)
    }

    return fetch(input, {...init, signal: AbortSignal.any(signals)})
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
