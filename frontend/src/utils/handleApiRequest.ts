import {loginRoute, router} from "./router.ts";
import alerts from "./alerts.ts";
import {useAuthStore} from "../store/authStore.ts";

export async function handleApiRequest<T>(request: () => Promise<T>): Promise<T> {
    try {
        return await request()
    } catch (error: any) {
        if (error?.status === 401) {
            await useAuthStore().logout()

            await router.push(loginRoute)

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
