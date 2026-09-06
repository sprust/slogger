import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import {readonly, ref, type Ref} from 'vue'
import {ApiTokenStorage} from "./apiContainer.ts";

/**
 * The panel's single WebSocket client.
 *
 * The server side is the sconcur ws pool, which speaks a compatible subset of Pusher's
 * protocol — hence pusher-js with no client of its own. The connection goes to the API
 * host and port, not the panel's: it is the same nginx that serves every other request,
 * with a separate location carrying the upgrade.
 *
 * A pool that is off, unreachable, or gone mid-session is not a broken panel: listen()
 * answers null and every caller falls back to the polling it used before. Meanwhile this
 * keeps trying, once a second, for as long as there is a session — so a pool that comes
 * back is picked up on the callers' next attempt rather than on a page reload.
 */

type EchoClient = Echo<'pusher'>

/**
 * What the header's dot shows.
 *
 * `off` and `lost` look the same from a subscriber's side — both mean polling — but not
 * to whoever is looking at the panel: one is a pool nobody configured, the other one that
 * was configured and did not answer.
 */
export type WsStatus = 'off' | 'idle' | 'connecting' | 'connected' | 'lost'

interface ListenCallbacks {
    /** Called once the channel is actually subscribed, not when listen() returns. */
    onSubscribed?: () => void
    /** Called when this subscription, or the socket under it, turns out to be unusable. */
    onLost?: () => void
}

/**
 * The retry loop's beat: one attempt starts every second.
 *
 * Measured from the start of an attempt rather than from its failure, so the common case
 * — a pool that is down and refuses the socket at once — retries on the second, every
 * second, for as long as there is a session.
 */
const reconnectInterval = 1000

/**
 * How long one attempt may hang before it is written off.
 *
 * Longer than the beat on purpose. A socket that is refused reports it in milliseconds
 * and never reaches this; what does reach it is a connection still being made, and one
 * second is not enough of a chance to give that over a slow link. Shorter than pusher-js
 * would take on its own, though — it waits ten seconds before saying `unavailable`, and
 * a loop cannot beat once a second while an attempt of it lasts ten.
 */
const attemptDeadline = 3000

let attemptStartedAt = 0

let attemptDeadlineId: number | null = null

let client: EchoClient | null = null

/**
 * Whether the socket is currently gone.
 *
 * Not a verdict — a reconnect is already scheduled whenever this is true. It exists so
 * that one failure is reported once, however many events pusher-js raises about it.
 */
let connectionLost = false

let reconnectTimeoutId: number | null = null

/** The dot's state. Written here, read by the header. */
const status = ref<WsStatus>(restingStatus())

/** Where the status sits with no client: nothing is wrong, nothing is connected either. */
function restingStatus(): WsStatus {
    return appKey() === '' ? 'off' : 'idle'
}

/** How many live listeners each private channel has, so the last one out can leave it. */
const listenerCounts: Record<string, number> = {}

/** What to call when the socket turns out to be unusable — one per live listener. */
const lostHandlers = new Set<() => void>()

function appKey(): string {
    return import.meta.env.VITE_SCONCUR_WS_KEY ?? ''
}

/**
 * Asks the application to sign a subscription to one channel.
 *
 * Written out rather than left to pusher-js, which posts this as
 * `application/x-www-form-urlencoded` — and the sconcur http server hands PHP an empty
 * input for such a body, so `channel_name` never arrives and every private channel is
 * refused. JSON is what the rest of the panel speaks anyway.
 *
 * The token is read per authorization rather than kept: a session that ends takes the
 * client with it, and this way there is no stale copy in between.
 */
async function authorizeChannel(channelName: string, socketId: string): Promise<unknown> {
    const response = await fetch(`${import.meta.env.VITE_BACKEND_URL}/broadcasting/auth`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Authorization': `Bearer ${ApiTokenStorage.getToken()}`,
        },
        body: JSON.stringify({
            channel_name: channelName,
            socket_id: socketId,
        }),
    })

    if (!response.ok) {
        throw new Error(`Channel authorization failed: ${response.status}`)
    }

    return await response.json()
}

export class EchoContainer {
    constructor() {
        throw new Error("Forbidden!")
    }

    /** Whether a pool is configured at all. Nothing is retried when it is not. */
    public static isEnabled(): boolean {
        return appKey() !== ''
    }

    /** The connection as the header shows it. */
    public static status(): Readonly<Ref<WsStatus>> {
        return readonly(status)
    }

    /**
     * Opens the connection. Safe to call more than once.
     *
     * Called once a session exists, because channel authorization needs a token and
     * there is no point holding a socket nobody may subscribe on. Also the body of the
     * retry loop: a failure schedules another one of these a second later.
     */
    public static connect(): void {
        if (client || !this.isEnabled() || !ApiTokenStorage.getToken()) {
            return
        }

        this.clearReconnect()

        connectionLost = false

        attemptStartedAt = Date.now()

        const backendUrl = new URL(import.meta.env.VITE_BACKEND_URL)
        const secure = backendUrl.protocol === 'https:'
        const port = Number(backendUrl.port) || (secure ? 443 : 80)

        client = new Echo<'pusher'>({
            broadcaster: 'pusher',
            Pusher,
            key: appKey(),
            wsHost: backendUrl.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS: secure,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            cluster: '',
            authorizer: (channel: {name: string}) => ({
                authorize: (socketId: string, callback: (error: Error | null, data: any) => void) => {
                    authorizeChannel(channel.name, socketId)
                        .then(data => callback(null, data))
                        .catch(error => callback(error, null))
                },
            }),
        })

        // Stays red across the whole retry loop once something has failed. Showing every
        // attempt would make the dot flicker once a second and say nothing more.
        if (status.value !== 'lost') {
            status.value = 'connecting'
        }

        const connection = client.connector.pusher.connection

        connection.bind('state_change', ({current}: {current: string}) => {
            if (!client || connectionLost) {
                return
            }

            if (current === 'connected') {
                this.clearDeadline()

                status.value = 'connected'

                return
            }

            if (status.value !== 'lost') {
                status.value = 'connecting'
            }
        })

        // `unavailable` is pusher-js saying it could not reach the host and will keep
        // trying on its own schedule. This client is dropped and rebuilt instead, on a
        // schedule of one second, so that a pool coming back is noticed promptly.
        connection.bind('unavailable', () => this.reportLost())
        connection.bind('failed', () => this.reportLost())
        connection.bind('error', () => this.reportLost())

        attemptDeadlineId = window.setTimeout(
            () => {
                attemptDeadlineId = null

                if (status.value === 'connected') {
                    return
                }

                this.reportLost()
            },
            attemptDeadline
        )
    }

    /**
     * Closes the connection, forgets every subscription, and stops retrying.
     *
     * A session that ends has to take the client with it: its subscriptions were signed
     * for the person who is leaving, and the next one to sign in on this tab would
     * inherit them.
     */
    public static disconnect(): void {
        this.clearReconnect()
        this.clearDeadline()

        connectionLost = false

        this.tearDown()

        status.value = restingStatus()
    }

    /**
     * Listens on a private channel until the returned function is called.
     *
     * Returns null unless the socket is up right now — the caller's signal to poll
     * instead. A caller that keeps polling therefore keeps offering to subscribe, which
     * is how a pool that came back is picked up without a page reload. `onLost` is the
     * same signal arriving late.
     *
     * Channels are reference-counted because the same one can have several owners at
     * once: two requests can wait on the same dynamic index, and leaving on the first of
     * them to finish would take the channel away from the other.
     */
    public static listen(
        channel: string,
        event: string,
        handler: (payload: any) => void,
        {onSubscribed, onLost}: ListenCallbacks = {}
    ): (() => void) | null {
        this.connect()

        if (!client || status.value !== 'connected') {
            return null
        }

        const lost = onLost ?? (() => {
        })

        const subscription = client.private(channel).listen(event, handler)

        if (onSubscribed) {
            // Not the same moment as this call: subscribing is a round trip of its own,
            // and anything published before it finishes is gone — the bus keeps no
            // history. Closing that window has to happen from here.
            subscription.subscribed(onSubscribed)
        }

        // A refused subscription is silence that looks exactly like an idle channel. Only
        // this owner is told: the socket is fine, and dropping it over one channel would
        // put every other subscriber through a reconnect for nothing.
        subscription.error(() => {
            lostHandlers.delete(lost)

            lost()
        })

        listenerCounts[channel] = (listenerCounts[channel] ?? 0) + 1

        lostHandlers.add(lost)

        let stopped = false

        return () => {
            // Idempotent: a second call would decrement past another owner's
            // subscription and leave the channel from under it.
            if (stopped) {
                return
            }

            stopped = true

            lostHandlers.delete(lost)

            this.stop(channel, event, handler)
        }
    }

    private static stop(channel: string, event: string, handler: (payload: any) => void): void {
        if (!client) {
            return
        }

        const left = (listenerCounts[channel] ?? 1) - 1

        if (left > 0) {
            listenerCounts[channel] = left

            client.private(channel).stopListening(event, handler)

            return
        }

        delete listenerCounts[channel]

        client.leave(channel)
    }

    /** Drops the client and the bookkeeping, leaving the retry schedule alone. */
    private static tearDown(): void {
        lostHandlers.clear()

        Object.keys(listenerCounts).forEach(channel => delete listenerCounts[channel])

        // Cleared before the socket is closed, not after: closing it raises one last
        // state_change, and that handler reads this to know it is no longer speaking for
        // a live client.
        const closing = client

        client = null

        closing?.disconnect()
    }

    /**
     * The socket is gone. Sends every listener back to polling and starts trying again.
     *
     * The client is dropped rather than left to pusher-js's own reconnect: a caller that
     * asks to listen in the meantime has to be told there is nothing to listen on, and a
     * client that is retrying looks the same as one that is connected.
     */
    private static reportLost(): void {
        if (connectionLost) {
            return
        }

        connectionLost = true

        this.clearDeadline()

        status.value = 'lost'

        const handlers = [...lostHandlers]

        this.tearDown()

        handlers.forEach(handler => handler())

        this.scheduleReconnect()
    }

    /**
     * One more attempt, a second from now, for as long as there is a session.
     *
     * The loop ends by itself rather than by a counter: connect() does nothing without a
     * token or an app key, and then nothing schedules the attempt after it.
     */
    private static scheduleReconnect(): void {
        if (reconnectTimeoutId !== null) {
            return
        }

        // What is left of this attempt's second. A socket refused outright waits nearly
        // all of it; one that hung until the deadline waits none.
        const wait = Math.max(0, reconnectInterval - (Date.now() - attemptStartedAt))

        reconnectTimeoutId = window.setTimeout(
            () => {
                reconnectTimeoutId = null

                // A failure inside schedules the next attempt; a refusal — no session,
                // no app key — schedules nothing, and the loop ends there.
                this.connect()
            },
            wait
        )
    }

    private static clearReconnect(): void {
        if (reconnectTimeoutId !== null) {
            window.clearTimeout(reconnectTimeoutId)
            reconnectTimeoutId = null
        }
    }

    private static clearDeadline(): void {
        if (attemptDeadlineId !== null) {
            window.clearTimeout(attemptDeadlineId)
            attemptDeadlineId = null
        }
    }
}
