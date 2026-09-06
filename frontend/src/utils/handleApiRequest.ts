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

/** How long the fallback waits between retries when there is no ws pool to wait on. */
const indexPollInterval = 1000

/** How finely the wait is cut, so a closed dialog is noticed without waiting it out. */
const indexWaitStep = 100

/**
 * One wait for the signal that the index a request is blocked on has been built.
 *
 * Answers false when the user closed the dialog — the request is not to be repeated —
 * and true both when the signal arrived and when the wait ran out.
 */
function waitForSignal(
    timeout: number,
    pendingRequestStore: ReturnType<typeof usePendingRequestStore>,
    onSignal: (resolve: () => void) => void
): Promise<boolean> {
    let timerId: number | null = null

    return new Promise<boolean>((resolve) => {
        let waited = 0

        onSignal(() => {
            if (timerId !== null) {
                window.clearTimeout(timerId)
                timerId = null
            }

            resolve(true)
        })

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
}

export async function handleApiRequest<T>(request: () => Promise<T>): Promise<T> {
    const pendingRequestStore = usePendingRequestStore()

    try {
        return await request()
    } catch (error: any) {
        if (error?.status === 412) {
            pendingRequestStore.open(error?.error?.data ?? null)

            // The subscription outlives the loop, and that is the point. Taken and
            // dropped per wait, it would be re-established after every retry, with a
            // /broadcasting/auth round trip in between — and a frame landing in that gap
            // is gone, because the bus keeps no history. Held across the retries, the only
            // window is the one before the first subscription, which onSubscribed closes.
            let watchedIndexId: string | null = null
            let unsubscribe: (() => void) | null = null

            // Set by a frame that arrived while nothing was waiting on it — between two
            // waits, that is, which is exactly where a retry sits.
            let signalled = false

            let onBuilt: () => void = () => {
            }

            const signal = () => {
                signalled = true

                onBuilt()
            }

            // Reached from three places — a change of index, a lost socket, and the exit
            // below — so it lives here rather than being written out at each of them.
            const release = (): void => {
                unsubscribe?.()
                unsubscribe = null
            }

            const watch = (indexId: string | null): void => {
                release()

                // A frame for the index being left behind is not an answer about the one
                // being taken up: carried over, it would skip a wait and spend a request
                // on a 412 that was already certain.
                signalled = false

                if (!indexId) {
                    return
                }

                unsubscribe = EchoContainer.listen(
                    `sl-trace-index.${indexId}`,
                    '.index.built',
                    signal,
                    {
                        // Not about the index being ready: the build was already running
                        // when this asked to listen, and anything published before the
                        // channel went live is lost. Retrying the moment it starts
                        // listening is what closes that window, once per index.
                        onSubscribed: signal,
                        // Ends the wait that is running, without marking a signal: there
                        // is nothing to report, and the next round has to go to the poll
                        // rather than skip its wait as well.
                        onLost: () => {
                            release()

                            onBuilt()
                        },
                    }
                )
            }

            try {
                while (true) {
                    const indexId = pendingRequestStore.data?.id ?? null

                    // Each 412 names whichever index is now in the way; the same one keeps
                    // the subscription it already has.
                    if (indexId !== watchedIndexId) {
                        watchedIndexId = indexId

                        watch(indexId)
                    }

                    let shouldContinue = true

                    if (signalled) {
                        // Arrived while this was retrying rather than waiting.
                        signalled = false
                    } else {
                        shouldContinue = await waitForSignal(
                            // No pool, or no index to wait on: ask again on a timer, the
                            // way this did before there was anything to wait for.
                            unsubscribe ? indexWaitTimeout : indexPollInterval,
                            pendingRequestStore,
                            (resolve) => {
                                onBuilt = resolve
                            }
                        )

                        onBuilt = () => {
                        }

                        signalled = false
                    }

                    if (!shouldContinue) {
                        return undefined as T
                    }

                    try {
                        return await request()
                    } catch (retryError: any) {
                        if (retryError?.status === 412) {
                            // Possibly a different index this time — the loop above
                            // re-subscribes only when the id actually changes.
                            pendingRequestStore.setData(retryError?.error?.data ?? null)
                            continue
                        }

                        error = retryError

                        break
                    }
                }
            } finally {
                release()

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
