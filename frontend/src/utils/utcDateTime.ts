// Up to six fractional digits, because the end of a graph bucket is a microsecond before
// the next bucket starts. A shorter fraction still matches, so ordinary values are unaffected.
const plainDateTimePattern =
    /^(\d{4})-(\d{2})-(\d{2})(?:[T\s])(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?$/

function parseDateTimeAsUtc(value: string): Date | null {
    const matched = value.trim().match(plainDateTimePattern)

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
        fraction = '0'
    ] = matched

    return new Date(Date.UTC(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hours),
        Number(minutes),
        Number(seconds),
        // A Date holds milliseconds and nothing finer, so the rest is cut here. What goes
        // to the backend keeps every digit - see normalizeUtcDateTime.
        Number(fraction.padEnd(3, '0').slice(0, 3))
    ))
}

export function zeroPad(value: number, size: number = 2): string {
    return String(value).padStart(size, '0')
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

    const matched = value.trim().match(plainDateTimePattern)
    const fraction = matched?.[7]

    // A bucket end carries microseconds, and a Date would round them away. Mark the text
    // as UTC instead of parsing it, so the bound reaches the backend with every digit.
    if (matched && fraction && fraction.length > 3) {
        const [, year, month, day, hours, minutes, seconds = '00'] = matched

        return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}.${fraction}Z`
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

/**
 * The instant a filter bound denotes, in milliseconds, whichever of its shapes it is in:
 * the normalized `...Z` string the payload carries, or the plain `YYYY-MM-DD HH:MM:SS` the
 * backend answers a graph window with. null for a bound that denotes nothing, which is
 * what an empty one is.
 *
 * Here rather than at the caller because this file is where the two shapes are already
 * known; a caller comparing them as strings would read 05:15:55 as later than
 * 2026-09-06T03:15:59.999999Z.
 */
export function utcTimestamp(value: string | Date | undefined | null): number | null {
    const normalized = normalizeUtcDateTime(value)

    if (!normalized) {
        return null
    }

    const time = new Date(normalized).getTime()

    return Number.isNaN(time) ? null : time
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
