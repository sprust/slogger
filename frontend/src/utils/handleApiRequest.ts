import {router, routes} from "./router.ts";
import alerts from "./alerts.ts";
import {useAuthStore} from "../store/authStore.ts";

export async function handleApiRequest<T>(request: Promise<T>): Promise<T> {
    return request.catch<T>((error): any => {
        if (error.status === 401) {
            useAuthStore().logout()

            router.push(routes.login)

            return
        }

        handleApiError(error)
    })
}

export function handleApiError(error: any) {
    let message = '' + error?.error?.message

    if (!message) {
        message = error.statusText
    }
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
