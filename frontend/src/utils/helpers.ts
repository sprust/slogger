import {valueIsSelected} from "./valueWasSelected.ts";
import {
    getPeriodPresetEnumByValue, PeriodPresetEnum,
    TraceAggregatorPayload,
    TraceStateParameters
} from "../components/pages/trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {
    TraceAggregatorService
} from "../components/pages/trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import alerts from "./alerts.ts";

function parseDateTimeAsUtc(value: string): Date | null {
    const matched = value.trim().match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[T\s])(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,3}))?)?$/
    )

    if (!matched) {
        return null
    }

    const [
        ,
        year,
        month,
        day,
        hours,
        minutes,
        seconds = '0',
        milliseconds = '0'
    ] = matched

    return new Date(Date.UTC(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hours),
        Number(minutes),
        Number(seconds),
        Number(milliseconds.padEnd(3, '0'))
    ))
}

function zeroPad(value: number, size: number = 2): string {
    return String(value).padStart(size, '0')
}

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
            alerts.error('Unable to copy to clipboard')
        }

        document.body.removeChild(textArea);
    }
}

export function normalizeUtcDateTime(value: string | Date | undefined | null): string | undefined {
    if (!value) {
        return undefined
    }

    if (value instanceof Date) {
        return new Date(Date.UTC(
            value.getFullYear(),
            value.getMonth(),
            value.getDate(),
            value.getHours(),
            value.getMinutes(),
            value.getSeconds(),
            value.getMilliseconds()
        )).toISOString()
    }

    const parsedUtcDate = /(?:Z|[+-]\d{2}:\d{2})$/.test(value)
        ? null
        : parseDateTimeAsUtc(value)

    const date = parsedUtcDate ?? new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toISOString()
}

export function makeUtcPickerDate(value: string | Date | undefined | null): Date | null {
    if (!value) {
        return null
    }

    const date = value instanceof Date ? value : new Date(value)

    if (Number.isNaN(date.getTime())) {
        return null
    }

    return new Date(
        date.getUTCFullYear(),
        date.getUTCMonth(),
        date.getUTCDate(),
        date.getUTCHours(),
        date.getUTCMinutes(),
        date.getUTCSeconds(),
        date.getUTCMilliseconds()
    )
}

export function formatUtcDateTime(value: string | Date | undefined | null): string {
    if (!value) {
        return ''
    }

    const parsedUtcDate = typeof value === 'string' && !/(?:Z|[+-]\d{2}:\d{2})$/.test(value)
        ? parseDateTimeAsUtc(value)
        : null

    const date = value instanceof Date ? value : (parsedUtcDate ?? new Date(value))

    if (Number.isNaN(date.getTime())) {
        return ''
    }

    return [
        date.getUTCFullYear(),
        zeroPad(date.getUTCMonth() + 1),
        zeroPad(date.getUTCDate())
    ].join('-') + ' ' + [
        zeroPad(date.getUTCHours()),
        zeroPad(date.getUTCMinutes()),
        zeroPad(date.getUTCSeconds())
    ].join(':')
}

export function makeGeneralFiltersTitles(
    state: TraceStateParameters,
    services: Array<TraceAggregatorService>
): string[] {
    const payload = state.payload

    const titles = new Array<string>()

    const loggedAtFromSelected = payload.logging_from_preset !== PeriodPresetEnum.Custom
        || (payload.logging_from && valueIsSelected(payload.logging_from))

    const loggedAtToSelected = payload.logging_to && valueIsSelected(payload.logging_to)

    if (loggedAtFromSelected || loggedAtToSelected) {
        let loggedAtFrom: string | null = null

        if (payload.logging_from_preset === PeriodPresetEnum.Custom) {
            if (payload.logging_from) {
                loggedAtFrom = payload.logging_from
            }
        } else {
            loggedAtFrom = snackToTitle(
                getPeriodPresetEnumByValue(payload.logging_from_preset ?? '')
            )
        }

        titles.push(
            'Logged at: ' + (loggedAtFromSelected ? loggedAtFrom : '∞') + '-'
            + (loggedAtToSelected ? payload.logging_to : '∞')
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

export function makeGraphTitles(
    showGraph: boolean,
    fields: Array<string>,
    period: string,
    step: string,
): string[] {
    if (!showGraph) {
        return []
    }

    const titles: Array<string> = [
        'Graph',
        'Period: ' + period,
        'Step: ' + step,
    ]

    if (!fields) {
        return titles
    }

    const fieldsTitles: Array<string> = []

    fields.forEach(
        function (field: string) {
            fieldsTitles.push(field)
        }
    )

    titles.push('Fields: ' + fieldsTitles.join(','))

    return titles
}

export function makeNow(): string {
    return formatUtcDateTime(new Date())
}

export function makeStartOfDay(): string {
    const now = new Date()

    return [
        now.getUTCFullYear(),
        zeroPad(now.getUTCMonth() + 1),
        zeroPad(now.getUTCDate())
    ].join('-') + ' 00:00:00'
}

export function snackToTitle(text: string): string {
    return text.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ')
}

export async function readStream(stream: ReadableStream<Uint8Array>): Promise<string> {
    const reader = stream.getReader()
    const decoder = new TextDecoder()
    let result = ''

    try {
        while (true) {
            const { done, value } = await reader.read()
            if (done) {
                break
            }
            result += decoder.decode(value, { stream: true })
        }

        result += decoder.decode()

        return result
    } finally {
        reader.releaseLock()
    }
}
