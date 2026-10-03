import type {DashboardTraceMetric} from "../../dashboard/store/dashboardMetricsStore.ts";

const slotsCount = 96

const slotMinutes = 15

const lastSlotMinutes = 23 * 60 + 45

function slotTime(index: number): string {
    const minutes = lastSlotMinutes - (slotsCount - 1 - index) * slotMinutes
    const hours = String(Math.floor(minutes / 60)).padStart(2, '0')
    const rest = String(minutes % 60).padStart(2, '0')

    return `2026-09-15 ${hours}:${rest}:00`
}

function base(index: number): number {
    const hour = Math.floor((index * slotMinutes) / 60)
    const daytime = hour >= 8 && hour < 20 ? 1 : 0.35

    return Math.round((520 + ((index * 37) % 60)) * daytime)
}

export const demoMetricSlots: Array<string> = Array.from({length: slotsCount}, (_, index) => slotTime(index))

const spikeSlots: Record<string, number> = {
    '2026-09-15 14:15:00': 3400,
    '2026-09-15 14:30:00': 3550,
}

const lagSlot = '2026-09-15 14:30:00'

const catchUpSlot = '2026-09-15 14:45:00'

const lagged = 1950

export const demoMetricRows: Array<DashboardTraceMetric> = demoMetricSlots.flatMap((slot: string, index: number) => {
    const requests = spikeSlots[slot] ?? base(index)
    const queue = Math.round(base(index) * 0.3)

    let stored = requests

    if (slot === lagSlot) {
        stored = requests - lagged
    } else if (slot === catchUpSlot) {
        stored = requests + lagged
    }

    return [
        {type: 'request', timestamp: slot, logged: requests, buffered: requests, stored: stored},
        {type: 'queue', timestamp: slot, logged: queue, buffered: queue, stored: queue},
    ]
})
