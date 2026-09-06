import {router, routes} from "./router.ts";
import alerts from "./alerts.ts";
import {useAuthStore} from "../store/authStore.ts";
import {usePendingRequestStore} from "../store/pendingRequestStore.ts";
import {EchoContainer} from "./echoContainer.ts";

/**
 * How long to wait for the "index built" frame before repeating the request anyway.
 *
 * The bus keeps no history, so a frame can be missed — the worst case has to stay the
 * behaviour this replaced, a retry, not a wait that never ends.
 */
const indexWaitTimeout = 15000

/**
 * The same, for the first wait on a given index — shorter, because that one is the wait
 * that can have missed its frame. Normally the subscription ends it well before this;
 * see onSubscribed below.
 */
const firstIndexWaitTimeout = 5000

/** How long the fallback waits between retries when there is no ws pool to wait on. */
const indexPollInterval = 1000

/** How finely the wait is cut, so a closed dialog is noticed without waiting it out. */
const indexWaitStep = 100

/**
 * Waits until the dynamic index the request is blocked on has been built.
 *
 * Answers false when the user closed the dialog — the request is not to be repeated.
 */
async function waitForIndex(
    indexId: string | null,
    firstWaitOnThisIndex: boolean,
    pendingRequestStore: ReturnType<typeof usePendingRequestStore>
): Promise<boolean> {
    let onBuilt: () => void = () => {
    }

    const unsubscribe = indexId
        ? EchoContainer.listen(
            `sl-trace-index.${indexId}`,
            '.index.built',
            () => onBuilt(),
            {
                // Once per index, and for a reason that has nothing to do with the index
                // being ready: the build was already running when this asked to listen,
                // and a frame published before the channel was live is gone — the bus
                // keeps no history. Retrying the moment the channel starts listening is
                // what closes that window. A repeat wait on the same index has been
                // listening since before the frame could have been published, so it has
                // nothing to close and simply waits.
                onSubscribed: firstWaitOnThisIndex ? () => onBuilt() : undefined,
            }
        )
        : null

    // No pool, or no index id to wait on: ask again on a timer, which is what this did
    // before there was anything to wait for.
    const timeout = unsubscribe
        ? (firstWaitOnThisIndex ? firstIndexWaitTimeout : indexWaitTimeout)
        : indexPollInterval

    let timerId: number | null = null

    try {
        return await new Promise<boolean>((resolve) => {
            let waited = 0

            onBuilt = () => resolve(true)

            const tick = () => {
                if (pendingRequestStore.cancelRequested) {
                    resolve(false)

                    return
                }

                waited += indexWaitStep

                if (waited >= timeout) {
                    resolve(true)

                    return
                }

                timerId = window.setTimeout(tick, indexWaitStep)
            }

            timerId = window.setTimeout(tick, indexWaitStep)
        })
    } finally {
        // Whether the frame arrived, the wait timed out or the dialog was closed, this
        // wait is over. Both of these outlive it otherwise: the channel would stay open,
        // and the tick chain would keep rescheduling itself to the end of the timeout,
        // once per hundred milliseconds, resolving a promise that is already settled.
        onBuilt = () => {
        }

        if (timerId !== null) {
            window.clearTimeout(timerId)
        }

        unsubscribe?.()
    }
}

export async function handleApiRequest<T>(request: () => Promise<T>): Promise<T> {
    const pendingRequestStore = usePendingRequestStore()

    try {
        return await request()
    } catch (error: any) {
        if (error?.status === 412) {
            pendingRequestStore.open(error?.error?.data ?? null)

            try {
                // Which index the last wait listened on. Keyed by id and not by a
                // counter: each 412 names whichever index is now in the way, and a wait
                // on one this loop has not listened on before is a first wait again.
                let watchedIndexId: string | null = null

                while (true) {
                    const indexId = pendingRequestStore.data?.id ?? null
                    const firstWaitOnThisIndex = indexId !== watchedIndexId

                    watchedIndexId = indexId

                    const shouldContinue = await waitForIndex(
                        indexId,
                        firstWaitOnThisIndex,
                        pendingRequestStore
                    )

                    if (!shouldContinue) {
                        return undefined as T
                    }

                    try {
                        return await request()
                    } catch (retryError: any) {
                        if (retryError?.status === 412) {
                            // Possibly a different index this time — the next wait is on
                            // whichever one the answer now names, and starts over as a
                            // first wait if it is not the one just watched.
                            pendingRequestStore.setData(retryError?.error?.data ?? null)
                            continue
                        }

                        error = retryError

                        break
                    }
                }
            } finally {
                pendingRequestStore.close()
            }
        }

        if (error?.status === 401) {
            await useAuthStore().logout()

            await router.push(routes.login)

            return undefined as T
        }

        handleApiError(error)

        return undefined as T
    }
}

export function handleApiError(error: any) {
    if (error?.name === 'AbortError') {
        return
    }

    let message = '' + (
        error?.error?.message
        ?? error?.error?.error
        ?? error?.statusText
        ?? error?.message
        ?? ''
    )

    if (!message) {
        message = 'Unknown error'
    }

    const errorsList = Object.values(error?.error?.errors ?? []).flat()

    if (errorsList.length) {
        message += '<br><ul>'

        errorsList.map((errorItemText) => {
            message += `<li>${errorItemText}</li>`
        })

        message += '</ul>'
    }

    console.error(error)

    alerts.error(message)
}
