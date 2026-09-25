// Level keys come from the backend as `<type>.<level>`: laravel.ERROR, nginx_error.crit,
// nginx_access.5xx, and `<type>.none` for an entry without a level.

export type LogTagType = 'primary' | 'success' | 'info' | 'warning' | 'danger'

const TYPE_TITLES: Record<string, string> = {
    laravel: 'Laravel',
    nginx_access: 'Nginx access',
    nginx_error: 'Nginx errors',
}

const LEVEL_ORDER: Record<string, Array<string>> = {
    laravel: ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
    nginx_access: ['1xx', '2xx', '3xx', '4xx', '5xx'],
    nginx_error: ['debug', 'info', 'notice', 'warn', 'error', 'crit', 'alert', 'emerg'],
}

const DANGER_LEVELS = new Set([
    'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY',
    'error', 'crit', 'alert', 'emerg',
    '5xx',
])

const WARNING_LEVELS = new Set(['WARNING', 'warn', '4xx'])

export function logTypeTitle(type: string): string {
    return TYPE_TITLES[type] ?? type
}

export function levelKeyType(key: string): string {
    return key.slice(0, key.indexOf('.'))
}

export function levelKeyName(key: string): string {
    return key.slice(key.indexOf('.') + 1)
}

export function levelTagType(key: string): LogTagType {
    const name = levelKeyName(key)

    if (DANGER_LEVELS.has(name)) {
        return 'danger'
    }

    if (WARNING_LEVELS.has(name)) {
        return 'warning'
    }

    if (name === '2xx') {
        return 'success'
    }

    return 'info'
}

// Known levels first in their own order, `none` and anything unknown after them.
export function compareLevelKeys(a: string, b: string): number {
    const order = LEVEL_ORDER[levelKeyType(a)] ?? []

    const positionA = order.indexOf(levelKeyName(a))
    const positionB = order.indexOf(levelKeyName(b))

    return (positionA === -1 ? order.length : positionA) - (positionB === -1 ? order.length : positionB)
}
