import type {SlowTracesEvent} from "../../watchers/store/incidentsStore.ts";
import type {Watcher} from "../../watchers/store/watchersStore.ts";
import {columnsByType, PayloadColumn} from "../../watchers/components/incidents/incidentEventColumns.ts";

export const demoIncidentColumns: Array<PayloadColumn> = columnsByType.slowTraces

export const demoIncidentEvents: Array<SlowTracesEvent> = [
    {
        id: 'demo-event-3',
        incident_id: 'demo-incident',
        occurred_at: '2026-09-15 14:41:00',
        payload: {
            settings: {duration: 2, window_minutes: 5},
            measured: {slowest: 3.912},
            groups: [
                {
                    service_id: 2,
                    type: 'request',
                    tags: ['POST /internal/invoices'],
                    count: 37,
                    duration_max: 3.912,
                    trace_id: 'billing-0f3a9c71',
                    trace_logged_at: '2026-09-15 14:38:12',
                },
            ],
        },
    },
    {
        id: 'demo-event-2',
        incident_id: 'demo-incident',
        occurred_at: '2026-09-15 14:31:00',
        payload: {
            settings: {duration: 2, window_minutes: 5},
            measured: {slowest: 4.704},
            groups: [
                {
                    service_id: 2,
                    type: 'request',
                    tags: ['POST /internal/invoices'],
                    count: 112,
                    duration_max: 4.704,
                    trace_id: 'billing-d635f961',
                    trace_logged_at: '2026-09-15 14:27:03',
                },
                {
                    service_id: 2,
                    type: 'request',
                    tags: ['GET /internal/invoices/{id}'],
                    count: 9,
                    duration_max: 2.318,
                    trace_id: 'billing-7c21e0b4',
                    trace_logged_at: '2026-09-15 14:29:40',
                },
            ],
        },
    },
    {
        id: 'demo-event-1',
        incident_id: 'demo-incident',
        occurred_at: '2026-09-15 14:22:00',
        payload: {
            settings: {duration: 2, window_minutes: 5},
            measured: {slowest: 2.651},
            groups: [
                {
                    service_id: 2,
                    type: 'request',
                    tags: ['POST /internal/invoices'],
                    count: 18,
                    duration_max: 2.651,
                    trace_id: 'billing-3e8d52f9',
                    trace_logged_at: '2026-09-15 14:20:47',
                },
            ],
        },
    },
]

export const demoChannelId = 1

export const demoWatchers: Array<Watcher> = [
    {
        id: 1,
        name: '',
        type: 'slowTraces',
        enabled: true,
        cooldown_seconds: 600,
        notification_channel_id: demoChannelId,
        notify_on_opened: true,
        notify_on_event: true,
        notify_on_closed: false,
        collect_since: '2026-09-01 09:00:00',
        last_checked_at: '2026-09-15 14:44:00',
        last_triggered_at: '2026-09-15 14:41:00',
        created_at: '2026-09-01 09:00:00',
        updated_at: '2026-09-01 09:00:00',
    },
]

export const demoWatcherTypeTitles: Record<string, string> = {
    slowTraces: 'Slow traces',
}

