import type {
    TraceAggregatorTreeRow,
    TraceTreeNode
} from "../../trace-aggregator/components/tree/store/traceAggregatorTreeStore.ts";

export const demoServiceNames: Record<number, string> = {
    1: 'gateway',
    2: 'billing',
    3: 'crm',
}

interface DemoTraceSpec {
    id: string,
    serviceId: number,
    type: string,
    tags: Array<string>,
    status: string,
    duration: number | null,
    memory: number,
    cpu: number,
    loggedAt: string,
    data: object,
    children: Array<DemoTraceSpec>,
}

const demoTraces: DemoTraceSpec = {
    id: 'gateway-7ea308c2',
    serviceId: 1,
    type: 'request',
    tags: ['POST /api/invoices', '201'],
    status: 'success',
    duration: 0.842,
    memory: 18.4,
    cpu: 12.1,
    loggedAt: '2026-09-15 14:27:03.120000',
    data: {request: {method: 'POST', uri: '/api/invoices', ip: '10.0.4.17'}, user: {id: 812}, response: {status: 201}},
    children: [
        {
            id: 'gateway-1b6d90e4',
            serviceId: 1,
            type: 'database',
            tags: ['mysql', 'select * from `users` where `id` = ?'],
            status: 'success',
            duration: 0.004,
            memory: 18.4,
            cpu: 12.1,
            loggedAt: '2026-09-15 14:27:03.124000',
            data: {connection: 'mysql', bindings: [812]},
            children: [],
        },
        {
            id: 'billing-d635f961',
            serviceId: 2,
            type: 'request',
            tags: ['POST /internal/invoices', '201'],
            status: 'success',
            duration: 0.781,
            memory: 24.2,
            cpu: 31.5,
            loggedAt: '2026-09-15 14:27:03.131000',
            data: {request: {method: 'POST', uri: '/internal/invoices'}, invoice: {amount: 14200, currency: 'EUR'}, response: {status: 201}},
            children: [
                {
                    id: 'billing-c9c24830',
                    serviceId: 2,
                    type: 'database',
                    tags: ['pgsql', 'select * from `customers` where `id` = ?'],
                    status: 'success',
                    duration: 0.006,
                    memory: 24.2,
                    cpu: 31.5,
                    loggedAt: '2026-09-15 14:27:03.135000',
                    data: {connection: 'pgsql', bindings: [812]},
                    children: [],
                },
                {
                    id: 'billing-4e2f71a0',
                    serviceId: 2,
                    type: 'http',
                    tags: ['GET crm/api/clients/{id}'],
                    status: 'success',
                    duration: 0.412,
                    memory: 24.6,
                    cpu: 31.5,
                    loggedAt: '2026-09-15 14:27:03.142000',
                    data: {request: {method: 'GET', uri: 'http://crm/api/clients/812'}, response: {status: 200}},
                    children: [
                        {
                            id: 'crm-90a1f3d2',
                            serviceId: 3,
                            type: 'request',
                            tags: ['GET /api/clients/{id}', '200'],
                            status: 'success',
                            duration: 0.398,
                            memory: 12.8,
                            cpu: 8.2,
                            loggedAt: '2026-09-15 14:27:03.149000',
                            data: {request: {method: 'GET', uri: '/api/clients/812'}, response: {status: 200}},
                            children: [
                                {
                                    id: 'crm-2c77b5e8',
                                    serviceId: 3,
                                    type: 'cache',
                                    tags: ['miss', 'client:812'],
                                    status: 'success',
                                    duration: 0.001,
                                    memory: 12.8,
                                    cpu: 8.2,
                                    loggedAt: '2026-09-15 14:27:03.151000',
                                    data: {key: 'client:812', hit: false},
                                    children: [],
                                },
                                {
                                    id: 'crm-61f0d9c4',
                                    serviceId: 3,
                                    type: 'database',
                                    tags: ['mysql', 'select * from `clients` where `id` = ?'],
                                    status: 'success',
                                    duration: 0.371,
                                    memory: 13.1,
                                    cpu: 8.4,
                                    loggedAt: '2026-09-15 14:27:03.153000',
                                    data: {connection: 'mysql', bindings: [812], rows: 1},
                                    children: [],
                                },
                            ],
                        },
                    ],
                },
                {
                    id: 'billing-a83e5b17',
                    serviceId: 2,
                    type: 'database',
                    tags: ['pgsql', 'insert into `invoices` (`customer_id`, `amount`) values (?, ?)'],
                    status: 'success',
                    duration: 0.012,
                    memory: 24.9,
                    cpu: 31.9,
                    loggedAt: '2026-09-15 14:27:03.556000',
                    data: {connection: 'pgsql', bindings: [812, 14200]},
                    children: [],
                },
                {
                    id: 'billing-5f19c6d3',
                    serviceId: 2,
                    type: 'queue',
                    tags: ['SendInvoiceEmail'],
                    status: 'started',
                    duration: null,
                    memory: 25.0,
                    cpu: 31.9,
                    loggedAt: '2026-09-15 14:27:03.571000',
                    data: {job: 'SendInvoiceEmail', queue: 'mail', attempts: 1},
                    children: [],
                },
            ],
        },
        {
            id: 'gateway-e04b2a69',
            serviceId: 1,
            type: 'event',
            tags: ['InvoiceAccepted'],
            status: 'success',
            duration: null,
            memory: 18.6,
            cpu: 12.3,
            loggedAt: '2026-09-15 14:27:03.958000',
            data: {invoice: {amount: 14200}},
            children: [],
        },
    ],
}

function makeRow(spec: DemoTraceSpec, parentId: string | null): TraceAggregatorTreeRow {
    return {
        service_id: spec.serviceId,
        parent_trace_id: parentId,
        trace_id: spec.id,
        type: spec.type,
        tags: spec.tags,
        status: spec.status,
        duration: spec.duration,
        memory: spec.memory,
        cpu: spec.cpu,
        logged_at: spec.loggedAt,
    }
}

function flatten(spec: DemoTraceSpec, parentId: string | null, depth: number, nodes: Array<TraceTreeNode>): TraceTreeNode {
    const node: TraceTreeNode = {
        id: spec.id,
        depth: depth,
        primary: makeRow(spec, parentId),
        children: [],
        collapsed: false,
        isHiddenByFilter: false,
        indicatorPercent: 0,
    }

    nodes.push(node)

    spec.children.forEach((child: DemoTraceSpec) => {
        node.children.push(flatten(child, spec.id, depth + 1, nodes))
    })

    return node
}

function collectData(spec: DemoTraceSpec, data: Record<string, object>): Record<string, object> {
    data[spec.id] = spec.data

    spec.children.forEach((child: DemoTraceSpec) => collectData(child, data))

    return data
}

export function makeDemoTree(): Array<TraceTreeNode> {
    const nodes: Array<TraceTreeNode> = []

    flatten(demoTraces, null, 0, nodes)

    return nodes
}

export const demoTraceData: Record<string, object> = collectData(demoTraces, {})

export function visibleTreeNodes(nodes: Array<TraceTreeNode>): Array<TraceTreeNode> {
    const visibleNodes: Array<TraceTreeNode> = []
    let collapsedDepth: number | null = null

    nodes.forEach((node: TraceTreeNode) => {
        if (collapsedDepth !== null) {
            if (node.depth > collapsedDepth) {
                return
            }
            collapsedDepth = null
        }

        visibleNodes.push(node)

        if (node.collapsed) {
            collapsedDepth = node.depth
        }
    })

    return visibleNodes
}
