import {router, routes} from "./router.ts";
import alerts from "./alerts.ts";
import {useAuthStore} from "../store/authStore.ts";
import {usePendingRequestStore} from "../store/pendingRequestStore.ts";

async function waitBeforeRetry(timeout: number, pendingRequestStore: ReturnType<typeof usePendingRequestStore>): Promise<boolean> {
    const step = 100
    let waited = 0

    while (waited < timeout) {
        await new Promise((resolve) => {
            setTimeout(resolve, step)
        })

        if (pendingRequestStore.cancelRequested) {
            return false
        }

        waited += step
    }

    return true
}

export async function handleApiRequest<T>(request: () => Promise<T>): Promise<T> {
    const pendingRequestStore = usePendingRequestStore()

    try {
        return await request()
    } catch (error: any) {
        if (error?.status === 412) {
            pendingRequestStore.open()

            try {
                while (true) {
                    const shouldContinue = await waitBeforeRetry(1000, pendingRequestStore)

                    if (!shouldContinue) {
                        return undefined as T
                    }

                    try {
                        return await request()
                    } catch (retryError: any) {
                        if (retryError?.status === 412) {
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
    let message = '' + (error?.error?.message ?? error?.error.error ?? error.statusText)

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
