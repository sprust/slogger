import type {TraceAggregatorItem} from "../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import type {TraceAggregatorDetailData} from "../../trace-aggregator/components/trace/store/traceAggregatorDataStore.ts";
import {demoServiceNames} from "./demoTraceTree.ts";

export type DemoJson = string | number | boolean | null | Array<DemoJson> | { [key: string]: DemoJson }

export interface DemoTrace {
    item: TraceAggregatorItem,
    data: { [key: string]: DemoJson },
}

interface DemoTraceSpec {
    id: string,
    parentId: string | null,
    serviceId: number,
    type: string,
    tags: Array<string>,
    status: string,
    duration: number | null,
    memory: number,
    cpu: number,
    loggedAt: string,
    data: { [key: string]: DemoJson },
}

interface DemoDataNode {
    key: string,
    value: string | number | boolean | null,
    children: Array<DemoDataNode> | null,
    can_be_filtered: boolean,
}

const specs: Array<DemoTraceSpec> = [
    {
        id: 'gateway-7ea308c2',
        parentId: null,
        serviceId: 1,
        type: 'request',
        tags: ['POST /api/invoices', '201'],
        status: 'success',
        duration: 0.842,
        memory: 18.4,
        cpu: 12.1,
        loggedAt: '2026-09-15 14:27:03.120000',
        data: {
            request: {method: 'POST', uri: '/api/invoices', ip: '10.0.4.17', headers: {'user-agent': 'crm-sync/2.4'}},
            user: {id: 812, roles: ['manager']},
            response: {status: 201},
        },
    },
    {
        id: 'billing-d635f961',
        parentId: 'gateway-7ea308c2',
        serviceId: 2,
        type: 'request',
        tags: ['POST /internal/invoices', '201'],
        status: 'success',
        duration: 0.781,
        memory: 24.2,
        cpu: 31.5,
        loggedAt: '2026-09-15 14:27:03.131000',
        data: {
            invoice: {
                amount: 14200,
                currency: 'EUR',
                items: [
                    {sku: 'A-17', price: 12000, qty: 1},
                    {sku: 'B-02', price: 2200, qty: 1},
                ],
            },
            response: {status: 201},
        },
    },
    {
        id: 'billing-3e8d52f9',
        parentId: 'gateway-41c07a3e',
        serviceId: 2,
        type: 'request',
        tags: ['POST /internal/invoices', '504'],
        status: 'failed',
        duration: 4.704,
        memory: 25.1,
        cpu: 33.0,
        loggedAt: '2026-09-15 14:27:41.502000',
        data: {
            invoice: {amount: 950, currency: 'EUR', items: [{sku: 'C-11', price: 950, qty: 1}]},
            response: {status: 504},
            error: {code: 'CRM_TIMEOUT', message: 'crm did not answer in 4 seconds'},
        },
    },
    {
        id: 'billing-5f19c6d3',
        parentId: 'billing-d635f961',
        serviceId: 2,
        type: 'queue',
        tags: ['SendInvoiceEmail'],
        status: 'success',
        duration: 0.214,
        memory: 22.7,
        cpu: 9.4,
        loggedAt: '2026-09-15 14:27:04.010000',
        data: {
            job: 'SendInvoiceEmail',
            attempts: 3,
            payload: {invoice_id: 5521, to: 'accounting@example.com'},
        },
    },
    {
        id: 'crm-90a1f3d2',
        parentId: 'billing-4e2f71a0',
        serviceId: 3,
        type: 'request',
        tags: ['GET /api/clients/{id}', '200'],
        status: 'success',
        duration: 0.398,
        memory: 12.8,
        cpu: 8.2,
        loggedAt: '2026-09-15 14:27:03.149000',
        data: {
            request: {method: 'GET', uri: '/api/clients/812'},
            client: {id: 812, tags: ['vip', 'eu'], manager: null},
            response: {status: 200},
        },
    },
    {
        id: 'gateway-b52e19d7',
        parentId: null,
        serviceId: 1,
        type: 'request',
        tags: ['GET /api/reports', '200'],
        status: 'success',
        duration: 1.812,
        memory: 41.3,
        cpu: 64.0,
        loggedAt: '2026-09-15 14:26:58.774000',
        data: {
            request: {method: 'GET', uri: '/api/reports?year=2026', ip: '10.0.2.9'},
            user: {id: 44, roles: ['admin', 'finance']},
            report: {rows: 1840, cached: false},
            response: {status: 200},
        },
    },
    {
        id: 'billing-81d4c0aa',
        parentId: null,
        serviceId: 2,
        type: 'event',
        tags: ['InvoicePaid'],
        status: 'success',
        duration: null,
        memory: 23.9,
        cpu: 11.2,
        loggedAt: '2026-09-15 14:26:31.305000',
        data: {
            invoice: {
                amount: 31000,
                items: [
                    {sku: 'A-17', price: 12000},
                    {sku: 'D-40', price: 19000},
                ],
            },
            paid: true,
        },
    },
    {
        id: 'billing-a83e5b17',
        parentId: 'billing-d635f961',
        serviceId: 2,
        type: 'database',
        tags: ['pgsql', 'insert into `invoices`'],
        status: 'success',
        duration: 0.012,
        memory: 24.9,
        cpu: 31.9,
        loggedAt: '2026-09-15 14:27:03.556000',
        data: {
            connection: 'pgsql',
            bindings: [812, 14200],
            time_ms: 12,
        },
    },
]

function toDataNode(value: DemoJson, key: string, canBeFiltered: boolean): DemoDataNode {
    if (value === null || typeof value !== 'object') {
        return {key, value, children: null, can_be_filtered: canBeFiltered}
    }

    const isList = Array.isArray(value)
    const entries: Array<[string, DemoJson]> = isList
        ? value.map((item: DemoJson, index: number) => [String(index), item])
        : Object.entries(value)

    return {
        key,
        value: null,
        children: entries.map(([childKey, childValue]) => toDataNode(
            childValue,
            key ? `${key}.${childKey}` : childKey,
            canBeFiltered && !isList,
        )),
        can_be_filtered: canBeFiltered,
    }
}

export function toTraceDetailData(data: { [key: string]: DemoJson }): TraceAggregatorDetailData {
    return toDataNode(data, '', true) as unknown as TraceAggregatorDetailData
}

export const demoTraces: Array<DemoTrace> = specs.map((spec: DemoTraceSpec) => ({
    item: {
        trace: {
            service: {id: spec.serviceId, name: demoServiceNames[spec.serviceId]},
            trace_id: spec.id,
            parent_trace_id: spec.parentId,
            type: spec.type,
            status: spec.status,
            tags: spec.tags,
            duration: spec.duration,
            memory: spec.memory,
            cpu: spec.cpu,
            has_profiling: false,
            additional_fields: [],
            logged_at: spec.loggedAt,
            created_at: spec.loggedAt,
            updated_at: spec.loggedAt,
        },
    },
    data: spec.data,
}))
