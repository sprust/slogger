import {ElMessage} from "element-plus";

export class TypesHelper {
    public static isValueInt(value: any): boolean {
        return Number.isInteger(value)
    }

    public static isValueFloat(value: any): boolean {
        return !Number.isInteger(value) && Number.isFinite(value)
    }

    public static isValueBool(value: any): boolean {
        return typeof value == "boolean"
    }
}

export async function copyToClipboard(value: string) {
    if (window.isSecureContext && navigator.clipboard) {
        await navigator.clipboard.writeText(value)
    } else {
        const textArea = document.createElement("textarea");

        textArea.value = value;

        document.body.appendChild(textArea);

        textArea.focus();

        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            handleApiError('Unable to copy to clipboard')
        }

        document.body.removeChild(textArea);
    }
}

export function handleApiError(error: any) {
    let message = '' + (error?.error?.message ?? error ?? 'Unknown error')

    const errorsList = Object.values(error?.error?.errors ?? []).flat()

    if (errorsList.length) {
        message += '<br><ul>'

        errorsList.map((errorItemText) => {
            message += `<li>${errorItemText}</li>`
        })

        message += '</ul>'
    }

    console.log(error)

    ElMessage.error({
        dangerouslyUseHTMLString: true,
        message: message,
        showClose: true,
        duration: 5000
    })
}

const zeroPad = (num: number, places: number): string => String(num).padStart(places, '0')

export function convertDateStringToLocal(dateString: string, withMicroseconds: boolean = true): string {
    const date = new Date(dateString)

    const newDate = new Date(date.getTime() + date.getTimezoneOffset() * 60 * 1000);

    const offset = date.getTimezoneOffset() / 60;

    newDate.setHours(date.getHours() - offset);

    const year = newDate.getFullYear().toString()
    const month = zeroPad(newDate.getMonth() + 1, 2)
    const day = zeroPad(newDate.getDate(), 2)
    const hours = zeroPad(newDate.getHours(), 2)
    const minutes = zeroPad(newDate.getMinutes(), 2)
    const seconds = zeroPad(newDate.getSeconds(), 2)
    const microseconds = newDate.getMilliseconds()

    const currentDate = new Date()

    let ymd: Array<string> = []

    if (currentDate.getFullYear() !== newDate.getFullYear()) {
        ymd = [year, month, day]
    } else {
        if (currentDate.getMonth() !== newDate.getMonth()) {
            ymd = [month, day]
        } else {
            if (currentDate.getDate() !== newDate.getDate()) {
                ymd = [month, day]
            }
        }
    }

    let dateResult = ymd.join('-') + ' ' + [hours, minutes, seconds.toLocaleString()].join(':')

    if (withMicroseconds) {
        dateResult += ('.' + microseconds)
    }

    return dateResult
}
