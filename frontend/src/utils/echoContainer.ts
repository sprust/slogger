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
 * Set once the connection has proved unusable.
 *
 * The app key lives in the panel's bundle and the pool's worker count lives in the
 * backend's .env — two independent switches. "Key present, pool off" is therefore a
 * reachable state, and it looks like a client that constructs fine and never connects.
 */
let connectionLost = false

/**
 * Whether the socket has ever been up.
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

        // `unavailable` is pusher-js saying it could not reach the host and will keep
        // trying; with the pool off that is the steady state, and waiting it out means a
        // panel that never updates and never falls back either.
        const connection = client.connector.pusher.connection

        connection.bind('connected', () => {
            everConnected = true
        })

        connection.bind('unavailable', () => this.reportLost())
        connection.bind('failed', () => this.reportLost())
        connection.bind('error', () => this.reportLost())
    }

    /**
     * Closes the connection and forgets every subscription.
     *
     * A session that ends has to take the client with it: its subscriptions were signed
     * for the person who is leaving, and the next one to sign in on this tab would
     * inherit them.
     */
    public static disconnect(): void {
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
     * Returns null when there is no usable pool — that is the caller's signal to poll
     * instead. `onLost` is the same signal arriving late, when the connection or the
     * subscription turns out to be unusable only after this returned. `onSubscribed` is
     * the moment the channel starts delivering, which is what closes the window between
     * asking to listen and actually listening.
     *
     * Channels are reference-counted because the same one can have several owners at
     * once: two requests can wait on the same dynamic index. Leaving on the first of them
     * to finish would take the channel away from the other.
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
            // Not the same moment as this call. Subscribing is a round trip of its own —
            // connect, POST /broadcasting/auth, send pusher:subscribe — and only when it
            // has finished is this channel actually listening. Anything published in
            // between is gone: the bus keeps no history. Whoever needs to close that
            // window has to do it from here, not from the line after this one.
            subscription.subscribed(onSubscribed)
        }

        // A refused subscription is silence that looks exactly like an idle channel.
        subscription.error(() => this.reportLost())

        listenerCounts[channel] = (listenerCounts[channel] ?? 0) + 1

        lostHandlers.add(lost)

        let stopped = false

        return () => {
            // Idempotent on purpose: a second call would otherwise decrement the count
            // past another owner's subscription and leave the channel from under it.
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
     * Declares the connection unusable and sends every listener back to polling.
     *
     * The client is dropped rather than left retrying: a later caller then takes the
     * poll path immediately instead of subscribing to a socket that is not there.
     */
    private static reportLost(): void {
        if (connectionLost || everConnected) {
            return
        }

        connectionLost = true

        const handlers = [...lostHandlers]

        this.disconnect()

        handlers.forEach(handler => handler())
    }
}
