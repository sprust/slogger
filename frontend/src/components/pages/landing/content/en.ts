import type {LandingText} from "./types.ts";
import {mcpScenariosEn} from "./mcpScenarios.en.ts";

export const en: LandingText = {
    topBar: {
        title: 'SLogger',
        subtitle: 'traces and logs of applications and microservices',
        login: 'Open panel',
        github: 'GitHub',
    },
    loginDialog: {
        title: 'Sign in to the panel',
        email: 'Email',
        password: 'Password',
        submit: 'Sign in',
        invalid: 'Invalid email or password.',
    },
    about: {
        title: 'What it is',
        paragraphs: [
            'SLogger receives traces from applications and microservices, stores them and gives a panel to search them, build call trees, filter by trace data and chart their figures.',
            'A trace is a record of one operation: an HTTP request, a database query, a queue job, a command or any operation of your own. The source can be any application that writes to the receiver\'s TCP socket using a simple protocol. For Laravel there is a ready library, slogger/laravel.',
        ],
        features: [
            {title: 'Call tree', text: 'A parent → children hierarchy of any depth, across service boundaries too.'},
            {title: 'Data filters', text: 'Conditions on any field of the trace payload: numbers, strings, booleans, null, field presence.'},
            {title: 'Graphs', text: 'Count, duration, memory and CPU over time intervals, with p50, p95 and p99 percentiles.'},
            {title: 'Intake metrics', text: 'How many traces each service sent, the buffer accepted and the store wrote in every 15 minutes of the last day.'},
            {title: 'Watchers', text: 'Rules that open an incident when the buffer grows, traces stop coming, there are too many of them or they are slow.'},
            {title: 'Notifications', text: 'Incidents go to Telegram, Slack or as JSON to your own address, with a delivery log.'},
            {title: 'MCP server', text: 'LLM clients such as Claude Code connect to SLogger and investigate traces and incidents. The tools only read.'},
            {title: 'Logs and cleanup', text: 'The logs of SLogger, its nginx and the receiver are viewable in the panel. Outdated traces are deleted automatically.'},
        ],
    },
    dataPath: {
        title: 'Data path',
        paragraphs: [
            'Intake and writing are separated to withstand load spikes. The Go receiver accepts messages over TCP and puts them into a MongoDB buffer as create and update operations. The transporter takes batches of up to 5000 records from the buffer and writes each batch with one insert into the ClickHouse traces table.',
            'A trace can arrive in halves: a create when the operation starts and an update when it finishes. A half waiting for its pair stays in the pendingTraces collection for up to 3 hours after its last write. Halves are merged without reading ClickHouse.',
            'If a store is unavailable, the batch is retried as it is with a pause from 1 to 30 seconds, and the attempts of its records are not spent. A record that failed 5 attempts is moved to the invalid trace buffer and kept there for 3 days.',
        ],
        nodes: {
            client: 'Client: any application with a TCP socket',
            receiver: 'Receiver (Go)',
            buffer: 'MongoDB buffer: creates and updates',
            transporter: 'Transporter: batches of up to 5000 records',
            pending: 'pendingTraces: halves waiting for a pair',
            clickhouse: 'ClickHouse: traces table',
            backend: 'Backend: Laravel on SConcur',
            panel: 'Panel and MCP clients',
        },
        edges: {
            clientReceiver: 'TCP: length prefix + JSON',
            receiverBuffer: 'Save (InsertMany)',
            transporterBuffer: 'FindForTransporter / DeleteByIds',
            transporterPending: 'FindMany / Apply (BulkWrite)',
            transporterClickhouse: 'Insert (JSONEachRow)',
            backendClickhouse: 'ClickhouseClient::select',
            panelBackend: 'HTTP via nginx',
        },
    },
    traceTree: {
        title: 'Trace and call tree',
        paragraphs: [
            'A trace has a service, type, tags, status, data, logging time, duration, memory and CPU. A trace can be written in two steps: at the start with the started status and at the end with the final status and duration. Unfinished traces are shown with the started status, so hanging operations are not lost.',
            'The parent → child link is set by the parent id and is not tied to a service. If a service passes its trace id to another service as the parent, the traces of the second service join the same tree. This way one incoming request is assembled into an end-to-end call tree across service boundaries.',
            'A tree is built in the background and kept for 24 hours. A tree of more than 200 000 nodes opens branch by branch.',
        ],
        demoCaption: 'Demo: the POST /api/invoices request in gateway calls billing, and billing calls crm. The arrow collapses a branch, a click on a row opens the trace data on the right, as in the panel. json shows the whole branch, indicate highlights the duration of calls inside the branch, tree marks the row as the current one.',
    },
    search: {
        title: 'Trace search',
        paragraphs: [
            'Search filters traces by services, types, tags, statuses, duration, memory, CPU and by any data field, nested ones included, such as user.id or request.path. Numbers are compared as numbers, strings by equality, containment, prefix and suffix, and there are checks for null and for field presence.',
            'Filters are saved as presets, and the filters of every search are written to the history.',
        ],
        traceList: {
            paragraphs: [
                'Below is the aggregator list on demo traces, and its filters work. A click on a type, tag or status in the list adds it to the filter, a second click removes it. A row expands into the trace data with search by keys and values, and the button next to a data field adds it to the conditions.',
                'Trace data is arbitrary JSON without a common schema: a request has request and response, an invoice has an array of items, a queue job has fields of its own. A condition is a dotted path and is checked on every trace that has such a path. If the path goes through an array of objects or ends in an array of values, the condition holds when at least one element matches. A trace without the field does not match a condition on its value; there is the "not exists" check for that. "Add to table" shows the field values in a column of their own.',
            ],
            examplesCaption: 'Data condition examples',
            examples: [
                {name: 'items', label: 'invoice.items.price > 10000', text: 'Array of objects: matches if at least one item costs more than 10 000.'},
                {name: 'roles', label: 'user.roles = "admin"', text: 'Array of values: matches if admin is among the roles.'},
                {name: 'error', label: 'error.code exists', text: 'Only traces that recorded an error have this field.'},
                {name: 'manager', label: 'client.manager = null', text: 'The key exists and is null. Traces without the key do not match.'},
                {name: 'status', label: 'response.status >= 500', text: 'Not every trace has this nested field: the event and the queue job have no response and do not match.'},
            ],
            resetExample: 'No conditions',
            found: 'Found',
            of: 'of',
            empty: 'No demo trace matches the conditions.',
        },
    },
    graphs: {
        title: 'Graphs',
        paragraphs: [
            'The same filters build graphs over a period from 5 minutes to a year: trace count, duration, memory and CPU. For duration, memory and CPU the average, minimum and maximum are drawn, and the p50, p95 and p99 percentiles are turned on in the legend. Graphs can also be built over numeric data fields.',
        ],
        filtersCaption: 'Demo graph filter',
        filters: ['service: billing', 'type: request', 'tags: POST /internal/invoices'],
        demoCaption: 'Demo: request count and duration in 5-minute intervals. The spike is from 14:20 to 14:40.',
    },
    metrics: {
        title: 'Intake metrics',
        paragraphs: [
            'For every service you see how many new traces came in each 15-minute interval of the last day, by three moments: when the source logged the trace (logged), when the receiver accepted it into the buffer (buffered) and when the transporter wrote it to ClickHouse (stored).',
            'The series diverge when something is wrong: a clogged buffer leaves stored behind buffered and then catches up in one spike, and a source with a skewed clock moves logged away from the other two. The receiver keeps the counters, and the traces table is not read for them.',
        ],
        demoCaption: 'Demo: billing over a day. At 14:30 writing fell behind intake and caught up in the next interval.',
    },
    watchers: {
        title: 'Watchers and notifications',
        demoWatcherName: 'billing: requests longer than 2 seconds',
        demoChannelName: 'Telegram: on-call',
        paragraphs: [
            'A watcher is a rule that opens an incident when something is wrong with the system. There are seven types: the buffer grows, invalid traces arrive, traces stop appearing, there are more of them than a threshold, they become slow, errors appear in the application log or in the receiver log. Trace watchers are narrowed by a filter on services, types, tags and statuses.',
            'The receiver counts traces for watchers in 15-second intervals, and once a minute a task checks them against the thresholds. The traces table is not read for this. Every trigger under an open incident is recorded as an event with the figures it triggered on.',
        ],
        ruleCaption: 'The watcher in the settings list. The 2-second threshold, the 5-minute window and the filter by the billing service and the request type are set in its form.',
        eventsCaption: 'Incident events. A row expands into trace groups.',
        channelsCaption: 'Channels',
        channels: [
            {name: 'Telegram', text: 'A bot writes to a chat, group or channel.'},
            {name: 'Slack', text: 'An incoming webhook writes to its channel.'},
            {name: 'Webhook', text: 'POST JSON {channel, text, sent_at} to your address, optionally signed with the X-Slogger-Token header.'},
        ],
        channelsNote: 'A watcher reports an opened incident, a new event under an open incident or a closed one — whatever is chosen in its settings. The delivery log is kept for 30 days.',
    },
    mcp: {
        title: 'MCP',
        scenarios: mcpScenariosEn,
        paragraphs: [
            'SLogger works as an MCP server. A connected LLM client, such as Claude Code, is asked a question in plain words and looks for the answer with SLogger tools: trace overviews, search, call trees, incidents and the logs of SLogger itself. The model and its tokens are on the client side. All tools only read.',
            'Access is granted per connection: every person or agent has a token of their own that can be disabled, reissued or deleted. For common tasks the server provides prompts: investigate_errors, investigate_latency, explain_incident, explain_trace.',
        ],
        scenariosCaption: 'Scenarios. The steps are the tool calls the model makes.',
        answerCaption: 'Answer',
        connectCaption: 'Connection',
        connectCommand: 'claude mcp add --transport http --scope user slogger-prod https://slogger.example.com/mcp \\\n  --header "Authorization: Bearer <token>"',
        connectNote: 'The MCP page of the panel shows the token and a ready command for your installation.',
    },
    stack: {
        title: 'Architecture and storage',
        runtimeCaption: 'Runtime',
        runtime: [
            'The backend is Laravel 12 on PHP 8.4 on top of SConcur: every HTTP request runs in its own PHP Fiber inside a long-lived process. The application is not booted again for every request.',
            'When one request needs several independent store calls, such as graph ranges or tree levels, they run in parallel. Calls to MongoDB, MySQL, Redis and ClickHouse go through the non-blocking SConcur clients.',
            'The trace receiver is a separate Go service. The panel is Vue 3, Vite and TypeScript.',
        ],
        storageCaption: 'Storage',
        storageNameColumn: 'Store',
        storageRoleColumn: 'What it keeps',
        storages: [
            {name: 'ClickHouse', role: 'Traces: the traces table partitioned by hour.'},
            {name: 'MongoDB', role: 'The receiver buffer, trace halves waiting for a pair, metric counters, tree cache, incidents and their events, the notification log.'},
            {name: 'MySQL', role: 'Users, services, authorization, watcher and channel settings.'},
            {name: 'RabbitMQ', role: 'Queues: tree building, notification delivery, trace cleanup.'},
            {name: 'Redis', role: 'Cache.'},
        ],
        cleanupCaption: 'Automatic cleanup',
        cleanup: [
            'Traces are kept for TRACES_LIFETIME_HOURS hours, 72 by default. Once an hour a task drops whole hourly partitions older than that, not individual rows.',
        ],
    },
}
