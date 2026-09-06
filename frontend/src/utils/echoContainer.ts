import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import {ApiTokenStorage} from "./apiContainer.ts";

/**
 * The panel's single WebSocket client.
 *
 * The server side is the sconcur ws pool, which speaks a compatible subset of Pusher's
 * protocol — hence pusher-js with no client of its own. The connection goes to the API
 * host and port, not the panel's: it is the same nginx that serves every other request,
 * with a separate location carrying the upgrade.
 *
 * The pool can be off, or up but unreachable. Everything here answers null in that case,
 * and every caller falls back to the polling it used before — a panel with no live
 * updates, not a broken one.
 */

type EchoClient = Echo<'pusher'>

interface ListenCallbacks {
    /** Called once the channel is actually subscribed, not when listen() returns. */
    onSubscribed?: () => void
    /** Called when the connection or the subscription turns out to be unusable. */
    onLost?: () => void
}

let client: EchoClient | null = null

/**
 * Set once the pool has proved unusable, and cleared by disconnect() with the session.
 *
 * The app key lives in the panel's bundle and the pool's worker count lives in the
 * backend's .env — two independent switches. "Key present, pool off" is therefore a
 * reachable state, and it looks like a client that constructs fine and never connects.
 */
let connectionLost = false

/**
 * Whether the socket has ever been up, for this session.
 *
 * A drop after a successful connection is pusher-js's own business — it reconnects and
 * the subscriptions come back. Only a connection that never happened means there is
 * nothing on the other end.
 */
let everConnected = false

/** How many live listeners each private channel has, so the last one out can leave it. */
const listenerCounts: Record<string, number> = {}

/** What to call when the connection turns out to be unusable — one per live listener. */
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

    public static isEnabled(): boolean {
        return appKey() !== '' && !connectionLost
    }

    /**
     * Opens the connection. Safe to call more than once.
     *
     * Called once a session exists, because channel authorization needs a token and
     * there is no point holding a socket nobody may subscribe on.
     */
    public static connect(): void {
        if (client || !this.isEnabled() || !ApiTokenStorage.getToken()) {
            return
        }

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

        const connection = client.connector.pusher.connection

        connection.bind('connected', () => {
            everConnected = true
        })

        // `unavailable` is pusher-js saying it could not reach the host and will keep
        // trying; with the pool off that is the steady state, and waiting it out means a
        // panel that never updates and never falls back either.
        connection.bind('unavailable', () => this.reportTransportLost())
        connection.bind('failed', () => this.reportTransportLost())
        connection.bind('error', () => this.reportTransportLost())
    }

    /**
     * Closes the connection and forgets every subscription.
     *
     * A session that ends has to take the client with it: its subscriptions were signed
     * for the person who is leaving, and the next one to sign in on this tab would
     * inherit them. The verdict on the pool goes with it too — it was reached under a
     * token that is gone, and a tab that signs in again should find out for itself.
     */
    public static disconnect(): void {
        connectionLost = false
        everConnected = false

        this.tearDown()
    }

    /** Drops the client and the bookkeeping, leaving the verdict alone. */
    private static tearDown(): void {
        lostHandlers.clear()

        Object.keys(listenerCounts).forEach(channel => delete listenerCounts[channel])

        if (!client) {
            return
        }

        client.disconnect()

        client = null
    }

    /**
     * Listens on a private channel until the returned function is called.
     *
     * Returns null when there is no usable pool — the caller's signal to poll instead.
     * `onLost` is that same signal arriving late.
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

        if (!client) {
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

        // A refused subscription is silence that looks exactly like an idle channel.
        subscription.error(() => this.reportLost())

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

    /**
     * The transport never came up.
     *
     * Guarded by everConnected, and only here: a drop after a successful connection is
     * pusher-js reconnecting, not a pool that is missing.
     */
    private static reportTransportLost(): void {
        if (everConnected) {
            return
        }

        this.reportLost()
    }

    /**
     * Declares the pool unusable and sends every listener back to polling.
     *
     * Unconditional, because the caller that matters most is a refused subscription:
     * that happens on a socket that connected perfectly well, so everConnected says
     * nothing about it, and nobody else will notice — a channel that was never
     * authorized is indistinguishable from one with nothing to say.
     *
     * The client is dropped rather than left retrying: a later caller then takes the
     * poll path immediately instead of subscribing to a socket that will not carry it.
     */
    private static reportLost(): void {
        if (connectionLost) {
            return
        }

        connectionLost = true

        const handlers = [...lostHandlers]

        this.tearDown()

        handlers.forEach(handler => handler())
    }
}
