import type {McpScenario} from "./types.ts";

export const mcpScenariosEn: Array<McpScenario> = [
    {
        name: 'load',
        title: 'Who loads it',
        question: 'Something is loading billing on prod. Find out who.',
        steps: [
            {
                tool: 'get_services',
                params: 'query: "billing"',
                result: 'billing is service 2',
            },
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from: 13:00, to: 16:00, by: ["minute10"]',
                result: 'from 14:20 to 14:40 there are 6 times more traces than usual',
            },
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from: 14:20, to: 14:40, by: ["type"]',
                result: 'the growth comes from request traces, other types are at their usual level',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: <a billing trace from the window>',
                result: 'billing requests are called by gateway POST /api/invoices',
            },
            {
                tool: 'search_traces',
                params: 'service_ids: [1], from: 14:20, to: 14:40, tags: ["POST /api/invoices"], data_fields: ["request.ip", "user.id"]',
                result: 'in the sample almost all requests come from 10.0.4.17 with user.id 812',
            },
        ],
        answer: 'The 14:20–14:40 spike in billing comes from calls by gateway POST /api/invoices. In the sample of gateway traces for that window almost all requests came from 10.0.4.17 by user 812. Other billing trace types did not grow.',
    },
    {
        name: 'errors',
        title: 'Why it fails',
        question: 'Why do billing API requests fail from time to time?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from, to: the last day, statuses: ["failed"], by: ["hour"]',
                result: 'errors come in waves of 10–15 minutes',
            },
            {
                tool: 'compare_trace_groups',
                params: 'group_a_statuses: ["failed"], by: "data.response.status"',
                result: 'failed traces almost always have 504, the rest 200',
            },
            {
                tool: 'compare_trace_groups',
                params: 'group_a_statuses: ["failed"], by: "tag"',
                result: 'failed traces mostly carry the POST /internal/invoices tag',
            },
            {
                tool: 'search_traces',
                params: 'statuses: ["failed"], data_filter: ["response.status = 504"]',
                result: 'examples of failed traces',
            },
            {
                tool: 'search_trace_tree',
                params: 'trace_id: <a failed trace>, statuses: ["failed"]',
                result: 'inside, a crm call fails with a timeout',
            },
        ],
        answer: 'POST /internal/invoices fails with 504: inside it the crm call gets no answer in time. The errors come in waves that match the load growth on crm. The investigate_errors prompt repeats this.',
    },
    {
        name: 'latency',
        title: 'What got slow',
        question: 'What has been slow in billing since last evening?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from, to: since the evening, by: ["hour"]',
                result: 'the p95 duration grew from 0.3 to 1.8 seconds after 19:00',
            },
            {
                tool: 'aggregate_traces',
                params: 'from: 19:00, to: now, by: ["type"]',
                result: 'p95 grew for request, database and queue are unchanged',
            },
            {
                tool: 'search_traces',
                params: 'types: ["request"], duration_from: 1.5',
                result: 'the slow requests are the GET /api/reports report',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: <a slow trace>',
                result: 'inside there are hundreds of identical database queries',
            },
        ],
        answer: 'Since 19:00 GET /api/reports has been slower: every report row runs a separate database query. Other trace types did not slow down. The investigate_latency prompt repeats this.',
    },
    {
        name: 'incident',
        title: 'The incident',
        question: 'What is the open incident on prod?',
        steps: [
            {
                tool: 'get_incidents',
                params: 'status: "opened"',
                result: 'an incident of the slowTraces watcher on billing is open',
            },
            {
                tool: 'get_incident_events',
                params: 'incident_id: <id>',
                result: 'the threshold is 2 seconds, the slowest trace took 4.7 seconds, groups by type',
            },
            {
                tool: 'aggregate_traces',
                params: 'from, to: the incident window, by: ["minute10"]',
                result: 'slow traces are concentrated in 14:20–14:40',
            },
            {
                tool: 'search_traces',
                params: 'from, to: the incident window, duration_from: 2',
                result: 'the slow traces are POST /internal/invoices',
            },
        ],
        answer: 'The billing slow trace watcher opened the incident: from 14:20 to 14:40 POST /internal/invoices took longer than 2 seconds, the slowest one 4.7 seconds. The explain_incident prompt repeats this.',
    },
    {
        name: 'trace',
        title: 'One trace',
        question: 'Explain the trace gateway-3b91d0e5.',
        steps: [
            {
                tool: 'get_trace',
                params: 'trace_id: "gateway-3b91d0e5"',
                result: 'POST /api/invoices, failed, 4.7 seconds',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: "gateway-3b91d0e5"',
                result: 'gateway → billing → crm, 14 nodes',
            },
            {
                tool: 'search_trace_tree',
                params: 'trace_id: "gateway-3b91d0e5", statuses: ["failed"]',
                result: 'the crm call failed',
            },
            {
                tool: 'get_trace_data',
                params: 'trace_id: <the failed node>',
                result: 'the data has the 504 response and the wait time',
            },
        ],
        answer: 'The request failed because billing did not get an answer from crm in time and returned 504. The other calls inside the tree succeeded. The explain_trace prompt repeats this.',
    },
    {
        name: 'release',
        title: 'After a release',
        question: 'What changed after the release at 14:00?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'from: 12:00, to: 16:00, by: ["hour", "type"]',
                result: 'count, errors and p95 by type before and after 14:00',
            },
            {
                tool: 'aggregate_traces',
                params: 'from: 14:00, to: 16:00, statuses: ["failed"], by: ["type"]',
                result: 'after the release queue started to fail',
            },
            {
                tool: 'compare_trace_groups',
                params: 'from: 14:00, to: 16:00, group_a_statuses: ["failed"], by: "tag"',
                result: 'one queue job fails',
            },
        ],
        answer: 'After 14:00 one queue job started to fail; the other trace types have the same count and p95.',
    },
    {
        name: 'data',
        title: 'Data search',
        question: 'Find failed invoices with an amount over 10 000.',
        steps: [
            {
                tool: 'get_trace_data_fields',
                params: 'type: "request"',
                result: 'there are request.uri, invoice.amount, response.status keys',
            },
            {
                tool: 'search_traces',
                params: 'statuses: ["failed"], data_filter: ["invoice.amount > 10000"], data_fields: ["invoice.amount"]',
                result: 'a list of traces with amounts',
            },
        ],
        answer: 'There are 12 failed requests with an amount over 10 000. The amounts and trace ids are in the list.',
    },
    {
        name: 'missing',
        title: 'No traces',
        question: 'Why are traces not shown in the panel?',
        steps: [
            {
                tool: 'get_trace_time_range',
                params: '',
                result: 'the last hour with traces was two hours ago',
            },
            {
                tool: 'get_incidents',
                params: 'status: "opened"',
                result: 'an incident of the bufferOverflow watcher is open',
            },
            {
                tool: 'search_slogger_logs',
                params: 'source: "Receiver", levels: ["error"]',
                result: 'the receiver cannot write to ClickHouse',
            },
        ],
        answer: 'Traces are received but not written: the receiver cannot write to ClickHouse, and the buffer grows. The open buffer watcher incident says the same.',
    },
    {
        name: 'installations',
        title: 'Two installations',
        question: 'Compare billing errors on stand and prod.',
        steps: [
            {
                tool: 'slogger-stand: aggregate_traces',
                params: 'statuses: ["failed"], by: ["type"]',
                result: 'stand errors by type',
            },
            {
                tool: 'slogger-prod: aggregate_traces',
                params: 'statuses: ["failed"], by: ["type"]',
                result: 'prod errors by type',
            },
        ],
        answer: 'Each installation is connected as a separate server and they share no data, so the model asks both and compares the answers. If no installation is named and several are connected, the model asks which one to look at.',
    },
]
