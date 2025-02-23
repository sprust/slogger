import {ElMessage} from "element-plus";
import {valueIsSelected} from "./valueWasSelected.ts";
import {TraceAggregatorPayload, TraceStateParameters} from "../components/pages/trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {TraceAggregatorService} from "../components/pages/trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

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

    const type = error.status === 400 ? "warning" : "error"

    ElMessage({
        dangerouslyUseHTMLString: true,
        message: message,
        type: type,
        showClose: true,
        duration: 5000
    })
}

const zeroPad = (num: number, places: number): string => String(num).padStart(places, '0')

export function convertDateStringToLocalFull(dateString: string | undefined | null) {
    if (!dateString) {
        return ''
    }

    return convertDateStringToLocal(dateString, false, false)
}

export function convertDateStringToLocal(
    dateString: string,
    withMicroseconds: boolean = true,
    collapseForCurrentDate: boolean = true,
): string {
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

    if (!collapseForCurrentDate || currentDate.getFullYear() !== newDate.getFullYear()) {
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

export function makeGeneralFiltersTitles(
    state: TraceStateParameters,
    services: Array<TraceAggregatorService>
): string[] {
    const payload = state.payload

    const titles = new Array<string>()

    const loggedAtFromSelected = payload.logging_from && valueIsSelected(payload.logging_from)
    const loggedAtToSelected = payload.logging_to && valueIsSelected(payload.logging_to)

    if (loggedAtFromSelected || loggedAtToSelected) {
        const loggedAtFrom: string | null = loggedAtFromSelected
            ? (state.startOfDay
                ? 'start of day'
                : convertDateStringToLocalFull(payload.logging_from))
            : null

        titles.push(
            'Logged at: ' + (loggedAtFromSelected ? loggedAtFrom : '∞') + '-'
            + (loggedAtToSelected ? convertDateStringToLocalFull(payload.logging_to) : '∞')
        )
    }

    if (payload.service_ids?.length) {
        const serviceNames: Array<string> = services
            .filter(
                (service: TraceAggregatorService) => payload.service_ids!.indexOf(service.id) != -1
            )
            .map(
                (service: TraceAggregatorService) => service.name
            )

        titles.push(`Services: ${serviceNames.join(',')}`)
    }

    if (payload.types?.length) {
        titles.push(`Types: ${payload.types.join(',')}`)
    }

    if (payload.tags?.length) {
        titles.push(`Tags: ${payload.tags.join(',')}`)
    }

    if (payload.statuses?.length) {
        titles.push(`Statuses: ${payload.statuses.join(',')}`)
    }

    if (payload.data?.filter?.length || payload.data?.fields?.length) {
        titles.push(`Some custom data`)
    }

    return titles
}

export function makeOtherFiltersTitles(payload: TraceAggregatorPayload): string[] {
    const titles = new Array<string>()

    if (payload.trace_id) {
        titles.push(
            'Trace id: ' + payload.trace_id + (payload.all_traces_in_tree ? ' (tree)' : '')
        )
    }

    const durationFromSelected = valueIsSelected(payload.duration_from)
    const durationToSelected = valueIsSelected(payload.duration_to)

    if (durationFromSelected || durationToSelected) {
        titles.push(
            'Duration: ' + (durationFromSelected ? payload.duration_from : '∞') + '-'
            + (durationToSelected ? payload.duration_to : '∞')
        )
    }

    const memoryFromSelected = valueIsSelected(payload.memory_from)
    const memoryToSelected = valueIsSelected(payload.memory_to)

    if (memoryFromSelected || memoryToSelected) {
        titles.push(
            'Memory: ' + (memoryFromSelected ? payload.memory_from : '∞') + '-'
            + (memoryToSelected ? payload.memory_to : '∞')
        )
    }

    const cpuFromSelected = valueIsSelected(payload.cpu_from)
    const cpuToSelected = valueIsSelected(payload.cpu_to)

    if (cpuFromSelected || cpuToSelected) {
        titles.push(
            'Cpu: ' + (cpuFromSelected ? payload.cpu_from : '∞') + '-'
            + (cpuToSelected ? payload.cpu_to : '∞')
        )
    }

    if (payload.has_profiling) {
        titles.push('Has profiling')
    }

    return titles
}

export function makeStartOfDay(): Date {
    const startOfDay = new Date()
    startOfDay.setUTCHours(Math.ceil(startOfDay.getTimezoneOffset() / 60), 0, 0, 0);

    return startOfDay
}
