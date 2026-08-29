**English** | [Русский](README.ru.md)

# SLogger

SLogger is an observability platform for ingesting, storing, and analyzing traces and logs from your applications and microservices.

It collects data about code execution (HTTP requests, queues, events, commands, and any custom operations), stores it in time-distributed storage, and provides a web panel for search, call-tree building, flexible filtering, and metric charts.

---

## Features

- Trace and log collection from any application over a simple socket protocol (the source language/stack does not matter).
- Call tree — a `parent → children` hierarchy with arbitrary nesting depth.
- Joining requests across services/microservices into a single end-to-end tree (distributed tracing).
- Flexible filtering by any field of the trace payload (`data`): numbers, strings, booleans, field-presence checks.
- Timeline charts for trace metrics — count, duration, memory, CPU — with aggregations and the same filtering as in search.
- Storage dashboard — collection sizes, memory and index usage.
- Runtime dashboard — live stats of the SConcur HTTP server: worker pool, RPS, CPU, memory, in-flight requests.
- Automatic cleanup of stale data.

---

## Architecture

### Data flow

```mermaid
flowchart TB
    src["Data source — any client that writes to a TCP socket (e.g. the Laravel library): trace start … finish"]
    receiver["Receiver (Go, TCP socket) — payload intake"]
    buffer["Buffer (MongoDB collection) — create (c) and update (u) operations"]
    shards["Hourly shards (MongoDB) traces_YYYY_MM_DD_HH_HH + view _traceTreesView"]
    backend["Backend (Laravel/SConcur) — master + HTTP workers, a request per fiber"]
    nginx["nginx (APP_PORT) — reverse proxy"]
    ui["Web panel (Vue 3) / API clients"]
    src -->|"TCP socket: 4-byte length prefix + JSON"| receiver
    receiver -->|"write to buffer"| buffer
    buffer -->|"transporter: continuous batches, upsert-merge"| shards
    shards -->|"reads (aggregations)"| backend
    ui -->|"HTTP"| nginx
    nginx -->|"proxy_pass → workers:SCONCUR_HTTP_PORT"| backend
```

### Components

- Backend — Laravel 12 / PHP 8.4 on [SConcur](https://github.com/sprust/sconcur), a concurrent coroutine HTTP runtime that executes each request in its own PHP Fiber inside a single long-lived process. The application stays in memory between requests, removing framework-bootstrap overhead and giving high throughput when ingesting and reading large volumes of traces. Heavy parallel shard queries are parallelized through `SConcur\WaitGroup`. See "SConcur runtime" below.
- sconcur-laravel — a bundled package (`packages/sconcur/sconcur-laravel/`, connected as a path repository) that binds Laravel to SConcur: the coroutine-scoped application, the HTTP worker, and the `sconcur:*` artisan commands.
- nginx — a reverse proxy in front of the HTTP workers; it is the only externally published port (`APP_PORT`, 8097 by default). The upstream host is resolved per request, so recreating the workers container does not require an nginx restart.
- Receiver — a standalone Go service (`servers/receiver/`) that accepts trace payloads over a TCP socket and writes them into the buffer.
- Storage — MongoDB (traces/logs), MySQL (users/services/auth), RabbitMQ (queues), Redis (cache). Reads and writes from the HTTP workers go through SConcur's non-blocking Mongo and MySQL drivers.
- Frontend — Vue 3 + Vite + TypeScript (`frontend/`).

Business logic is split into modules under `app/Modules/<ModuleName>/` with strict layer separation (Deptrac).

---

## Technical implementation

### SConcur runtime: a request per fiber

The backend does not run under php-fpm or Octane. HTTP requests are served by SConcur — a coroutine runtime in which every request gets its own PHP Fiber inside one long-lived process:

- Master and workers. `sconcur:servers:master:start` (started by supervisor in the `workers` container) is a supervisor over the worker pools: it spawns `SCONCUR_HTTP_WORKER_COUNT` processes as `php artisan sconcur:servers:http:start --masterPid=N`, restarts crashed and hung ones, and exposes a telemetry panel. A pool is a `groups` entry of the master config, and one master can supervise several unlike pools under one lock and one journal; here there is one, named `http`. All workers of it listen on the same port via `SO_REUSEPORT`; nginx proxies to them without knowing about the pool. A single worker can also be run standalone by the same `sconcur:servers:http:start` command. `sconcur:servers:master:status` and `:reload` take an optional `--group=NAME` to act on one pool instead of all of them.
- Coroutine-scoped application. Under concurrent fibers, the Octane model (clone the app + swap the global container) is unsafe: neighbouring requests would see each other's state. Instead, `bootstrap/app.php` builds `SConcur\Laravel\Foundation\AsyncApplication` — a drop-in subclass of `Illuminate\Foundation\Application` that moves per-request state into the coroutine context: `request`, `auth`, `session`, `cookie`, the config overlay (`config()->set`), the current route, the locale, `View::share`, and `defer`. Async mode is enabled only inside the HTTP worker; for CLI, queues, and cron the application behaves exactly as a stock Laravel one.
- Non-blocking I/O. Queries to MongoDB and MySQL go through SConcur drivers (`Model::sconcur()`), so a fiber waiting on the database yields the process to other requests instead of blocking it. Where a single request needs several shard queries at once, they are run in parallel through `SConcur\WaitGroup` (search, charts, tree building).
- Transaction caveat. Do not perform sconcur-async work (Mongo, sconcur-SQL, HTTP client, `Sleeper`) inside an open MySQL transaction: the blocking PDO connection is shared by the process, so while one fiber awaits, another can end up inside its transaction. Do async work before `beginTransaction` or after `commit` (or push it into a queue).
- Queues on the same runtime. `QUEUE_CONNECTION` defaults to `sconcur_rabbitmq`: a Laravel queue driver over the SConcur AMQP feature, plus a consumer pool that runs as another group of the same master. The `default`, `trace-tree` and `traces-clearing` queues used to be three supervisor programs — five, one and one processes, each blocked on its own queue; they are now one process reading all three at once with a coroutine per delivery, and the former process counts became per-queue weights in `SCONCUR_RABBITMQ_QUEUES`. A slow job costs one message rather than the worker. The wire format is the one `vladimir-yuldashev/laravel-queue-rabbitmq` writes — same body, same message properties, same `laravel.attempts` header — so a job published by either driver is readable and runnable by the other. The trace queue `slogger` is the fourth in the same pool. It used to be served by `slogger:dispatcher:start` from the external `slogger/laravel` package — a supervisor inside a supervisor, spawning its own `SLOGGER_DISPATCHER_QUEUE_WORKERS_COUNT` `queue:work` processes through Symfony Process; that worker count is the queue's weight now, and the command is no longer started by the supervisor. The work also became visible in the telemetry panel: the master hands the collector socket only to its own workers, so processes the dispatcher spawned never reported to it. `SendTracesJob` carries its own `$tries` and `$backoff`, and a job's own values win over the pool's, so its retry policy is unchanged. See `packages/sconcur/sconcur-laravel/README.md`.

- Periodic tasks on the same runtime. The cron and the dynamic index monitor used to be two supervisor programs — two processes, each with its own forever loop, its own `sleep()` and its own ad-hoc way of being stopped (a cache marker for the cron, a cache flag for the monitor). They are now two tasks of one coroutine pool — the `tasks` group of the same master that holds the HTTP pool and the queue consumers (`sconcur:tasks:start`, exactly one worker): a task implements `tick()` and nothing else — one pass of work — while the loop, the pauses, the error handling and the stop belong to the pool. A native `sleep()` would freeze the whole process, so the pause goes through `Sleeper` and suspends only its own coroutine: while the monitor builds an index in Mongo, the cron keeps ticking. Control is `sconcur:tasks:stop [--task=]` and `sconcur:tasks:restart [--task=]`, which post the command through the cache, so the pool can be managed from another container without knowing its pid; the old `cron:stop` and `trace-dynamic-indexes:monitor:stop` are gone. `cron:start` and `trace-dynamic-indexes:monitor:start` remain as a way to run a single task on its own. The pool reports its own telemetry: it runs no Go-side runtime to do that for it, so `TaskPoolTelemetry` samples RSS and CPU from `/proc` and pushes snapshots over the same open contract — a unix socket, a length prefix, JSON. The pool's ticks ride in the snapshot's `consumers` section — a tick is to a task what a delivery is to a consumer — so the dashboard's In-flight / Handled / Refused columns fill in on their own; the price is that the master's own totals count ticks alongside real deliveries (turn it off with `report_ticks`). This is temporary, until the Go side learns to report for a plain worker. Groups the panel says nothing about — a stopped pool, say — are filled in with zeros by the dashboard rather than dropped. Details: `packages/sconcur/sconcur-laravel/docs/task-pool.ru.md`.
Details on the bridge and the coroutine context: `packages/sconcur/sconcur-laravel/README.md` and its `docs/`.

### Runtime dashboard (SConcur stats)

The master's telemetry panel (`SCONCUR_HTTP_PANEL_PORT`, protected by `SCONCUR_HTTP_ADMIN_TOKEN`) is polled by the backend and rendered on the "Sconcur" dashboard tab: the master totals (workers, hung workers, CPU, RSS, goroutines, in-flight and completed requests, average duration), a per-group breakdown, a per-worker breakdown carrying the group each worker belongs to, and a chart with a rolling 5-minute window — RPS and CPU by default. A pool consuming a broker queue reports deliveries instead of requests, so its counters (delivered, acked, refused, in-flight) take the place of the request ones in its row and add their own chart metrics; an HTTP pool omits that section. RPS is derived on the client from the delta of completed requests between polls. If the panel host or the token is not configured, or the master is down, the tab shows the runtime as unavailable instead of erroring.

### Hourly database sharding

Traces are stored not in a single collection, but in periodic shard collections split by hour:

```
traces_YYYY_MM_DD_HH_HH      example: traces_2026_06_21_14_15  (hour 14:00–15:00)
```

On the first access to a given hour, the shard is created on demand and a set of base indexes is set up on it: `sid` (service), `tid` (trace), `ptid` (parent), `tp` (type), `st` (status), tags, `lat` (logged-at time), plus composite indexes. Active shard names are cached in memory.

Hourly slicing gives three advantages:

1. Period queries read only the relevant shards. A search over the last hour does not scan week-old data — it touches one or two collections instead of a single large one.
2. Deleting old data means dropping whole collections. Cleanup does not run an expensive `delete` over millions of documents — it drops the shards that fell out of the retention window (see "Automatic cleanup"). This is fast and does not load the database.
3. Dynamic indexes live together with their shards and are removed along with them, so they do not accumulate indefinitely (see "Per-query auto-indexing").

On top of all shards, MongoDB exposes a unifying view `_traceTreesView` — it combines all periodic collections into a single logical set and adds the collection name to every document. The call tree is built through this view even when a parent and its children landed in different hourly shards.

### Buffer → write to shard

Intake and write are decoupled to absorb load spikes:

1. Intake. The receiver accepts the payload over TCP and puts it into a buffer collection in MongoDB as a set of two kinds of operations: create (`c`) and update (`u`). A whole message batch is written with a single unordered `InsertMany` rather than a call per trace.
2. Transport. A background transporter continuously pulls batches from the buffer (up to ~1000 records in FIFO order) and writes them into the corresponding hourly shards. It pauses for one second only when the buffer is empty (or after a read error), then checks again.
3. Merge (upsert-merge). Writing to a shard is an `upsert` keyed by service + trace: the create and update operations of the same trace are merged into one resulting document.
4. Reliability. After a successful write the record is removed from the buffer; on error it is marked for retry (with a limited number of attempts).

The buffer smooths out peaks: the client hands off data quickly and does not wait for the write into the main storage.

Concurrency in the receiver is bounded rather than unlimited: at most 512 messages are handled at once, and at most 64 shard writes run in parallel in the transporter. When the limits are reached, the next socket read is simply delayed — backpressure reaches the sender instead of the process piling up a multi-gigabyte backlog, and the transporter does not starve the intake path of Mongo connections.

### Trace timeline: start separately → finish separately

A trace may be written in two stages, when the operation's outcome is not yet known at start time (the typical case for a parent trace):

1. Start — a trace-creation object is sent:
   - a `traceId` is assigned and the `parentTraceId` is captured (the current parent from context);
   - `status = started`, `duration = null` (the duration is not yet known);
   - type, tags, data, logged-at time, and snapshot memory/CPU values are recorded;
   - the started trace becomes the current parent — every trace that starts before its finish automatically becomes its child.

2. Finish — an update object for the same trace is sent:
   - the final `status` is set (`success` / `failed` / custom);
   - `duration` (actual elapsed time) is filled in;
   - memory and CPU are updated, and tags/data are supplemented if needed;
   - the previous parent is restored from the stack.

On the storage side, the creation and the finish are merged into one document (upsert by `tid`). This two-stage mechanism forms a timeline of nested operations: from the `parent → child` links and the start time / duration you can reconstruct what ran inside a request and in what order, which operations were nested into one another, and how long each took. Unfinished traces (a start with no finish) are visible with status `started`, which lets you catch operations that hung or crashed without a finish.

Update is not mandatory. Two stages are just one scenario, not a requirement. A trace can be written with a single creation message — for example, when it is not a parent but a single operation whose outcome is known immediately (a database query, an outbound HTTP call, sending an email, etc.). In that case the final `status`, `duration`, `memory`, `cpu` are set right in the creation, and no update is sent at all. Likewise, `ptid` is optional: a trace with no parent is a root trace (the top of the tree). So the minimum is a single creation object with its fields filled in; a parent and/or a subsequent update are added only when they are actually needed.

### Joining requests across services/microservices

The `parent → child` link is built on the `parentTraceId` / `traceId` identifiers and is not tied to a specific service. If service A passes its `traceId` to service B as the parent when starting an operation, then service B's traces will appear in the tree as children of service A's trace.

This forms an end-to-end call tree across microservice boundaries: a single inbound HTTP request can fan out into a chain of calls to several services, and all of it is assembled into one tree through `_traceTreesView`. Search, tree, and charts can be filtered by several services at once (`serviceIds`) — or built with no service binding at all.

### Per-query auto-indexing (dynamic indexes)

Traces are filtered by arbitrary `data` fields, and it is impossible to index all possible field combinations in advance. Therefore indexes are created automatically for a specific query:

- before a search, the system analyzes the set of filters (services, types, tags, statuses, duration/memory/CPU ranges, arbitrary `data` fields) and determines the required set of index fields;
- if a suitable index does not exist yet, it is created (asynchronously, marked in-progress), and the query waits for it to become ready;
- subsequent identical queries use the ready index and run fast.

Why dynamic indexes rather than permanent ones: indexes take up disk space and slow down writes, so keeping an index for every conceivable `data` field is expensive and wasteful. Dynamic indexes are therefore short-term (TTL on the order of a few days — currently 5) and are deleted automatically once they stop being used. Hourly sharding works in tandem here: an index is bound to its shards and is removed with them during cleanup, so it never accumulates indefinitely. The result is fast search over arbitrary fields combined with space savings — the system pays for an index only while it is actually needed.

### Flexible filtering by trace data

You can filter by any payload field, including nested ones (`user.id`, `request.path`, etc.):

- numbers — `=`, `≠`, `>`, `≥`, `<`, `≤`;
- strings — equals, contains, starts with, ends with;
- booleans — `true` / `false`;
- field presence — has a value / is absent.

These filters are assembled into a MongoDB aggregation pipeline and work together with the base filters (service, type, tags, status, duration/memory/CPU ranges, time period). A dynamic index is automatically raised for the selected set of fields.

### Trace metric charts

Besides paginated search, timeline charts are built over traces. You pick a period (from 5 minutes to a year) and a step (bucket granularity); the data is laid out across time intervals, and within each interval the metrics are computed:

- count of traces (`count`, aggregation — sum);
- duration (`duration`);
- memory (`memory`);
- CPU (`cpu`).

For duration/memory/CPU the average, minimum, and maximum are computed. Charts can additionally be built over numeric fields from `data`. The same set of filters as in search applies to charts, so you can watch metric dynamics for a specific service, operation type, tag, or an arbitrary condition on the data. Interval collection is parallelized, which makes charts fast to build even over large periods.

### Automatic cleanup

Stale traces are removed automatically. The retention period is set by the `TRACES_LIFETIME_DAYS` variable (default 3 days). Cleanup is a queued job (`ClearTracesJob`) and, thanks to hourly sharding, drops whole shard collections that fell out of the retention window instead of deleting individual documents. This is fast, does not fragment storage, and also removes the dynamic indexes associated with those shards.

---

## Tech stack

- PHP 8.4, Laravel 12, PSR-12 style (PHP CS Fixer)
- SConcur — concurrent coroutine HTTP runtime (long-running application), plus the bundled `sconcur/sconcur-laravel` bridge
- nginx — reverse proxy in front of the HTTP workers
- MongoDB (`mongodb/laravel-mongodb`, non-blocking SConcur driver) — traces and logs
- MySQL — users, services, auth
- RabbitMQ — queues; Redis — cache
- Go — trace receiver service (`servers/receiver/`)
- Vue 3 + Vite + TypeScript — web panel
- Static analysis: PHPStan; layer boundaries: Deptrac

---

## Data source and format

SLogger is not tied to a specific client. The data source can be any application in any language capable of writing to the receiver's TCP socket. The dataset is universal — the only requirement is that the payload conforms to the expected format.

For Laravel applications there is a ready-made library that handles trace start/finish, parent-context propagation (including across services), buffering, and sending data:

slogger/laravel → https://github.com/sprust/slogger-laravel

This is just one of the possible sources — your own client implementing the protocol below can send data just as well.

### Socket protocol

Communication is over TCP. Every message, in both directions, is sent with a 4-byte length prefix (big-endian `uint32`) followed by the body (UTF-8 JSON). The maximum body size is 10 MB.

1. Authentication. Right after connecting, the client sends a message with the service API token:

   ```json
   { "t": "<api_token>" }
   ```

   The token determines which service the traces belong to (created via `make art c=service:create`). The server replies with `ok` or an error text.

2. Sending traces. Then, within the same connection, the client sends trace messages in a loop; the server replies `received` to each one.

The connection is long-lived: the server does not close it while it is idle, so a client may hold an authenticated socket open between bursts of traces without reconnecting. A read timeout (30 s) applies only from the moment the first byte of a message arrives and covers the rest of the length prefix and the body — a sender stalling in the middle of a message still cannot hold the connection forever. Peers that vanish without closing the socket are reaped by TCP keep-alive (30 s).

### Trace message format

A message contains two optional fields — a batch of traces to create (`c`) and a batch of updates (`u`). The values of `c` and `u` are JSON strings (serialized arrays of objects), not nested arrays:

```json
{
  "c": "[ <traces to create> ]",
  "u": "[ <trace updates> ]"
}
```

Trace to create (the start stage):

```json
{
  "tid":  "9f1c…",          // trace ID (required)
  "ptid": "0b8a…",          // parent trace ID (optional; links into the tree, incl. across services)
  "tp":   "request",        // operation type (request, job, command, event, …)
  "st":   "started",        // status
  "tgs":  ["api", "v2"],    // tags (array)
  "dt":   { "path": "/x" }, // arbitrary data (JSON only, any structure)
  "dur":  null,             // duration (usually not yet known at start)
  "mem":  41.5,             // memory, % (optional)
  "cpu":  12.3,             // CPU, % (optional)
  "lat":  "2026-06-21 14:00:00.000000"  // logged-at time
}
```

Trace update (the finish stage):

```json
{
  "tid":  "9f1c…",          // the same trace ID
  "st":   "success",        // final status (success / failed / custom)
  "tgs":  ["api", "v2"],    // tags — overwrite existing ones if present (do not append)
  "dt":   { "code": 200 },  // data (JSON) — overwrites existing if present (does not append)
  "dur":  0.137,            // actual duration
  "mem":  43.1,             // memory, %
  "cpu":  15.0,             // CPU, %
  "plat": "2026-06-21 14:00:00.000000"  // parent's logged-at time
}
```

The field names are intentionally short (`tid`, `ptid`, `tp`, …) — this reduces the volume of transmitted and stored data.

Merging create and update. On the receiver side, a create and an update with the same `tid` are merged into one document (see "Buffer → write to shard" and "Trace timeline"). The update overwrites fields (`st`, `tgs`, `dt`, `dur`, `mem`, `cpu`) rather than appending to them — but only those that are actually present in the update; missing fields keep their values from the create. The order in which each field's value is taken: update → already-stored document → create. Therefore, if an update is persisted before the create, the update's data takes priority: a create that arrives later does not overwrite it, only backfills the missing fields (for example, the type `tp`).

---

## Installation

### Copy env files

```bash
make env-copy
```

### Configure environment variables

`.env`:

```dotenv
APP_ENV=production        # or local
APP_DEBUG=false           # true for local

# the root user is set by default
# to find user id and group id on linux: `id -u` and `id -g`
DOCKER_USER_ID=1000
DOCKER_GROUP_ID=1000

APP_PORT=8097             # external port of nginx in front of the SConcur HTTP workers

FRONTEND_DOCKER_COMMAND=${FRONTEND_DOCKER_SERVER_COMMAND}  # or ${FRONTEND_DOCKER_LOCAL_COMMAND}
FRONTEND_DOCKER_PORT=3075                                  # external port of the web panel

TRACES_LIFETIME_DAYS=3    # trace retention period in days

# SConcur HTTP runtime (the full set of knobs is in .env.example)
SCONCUR_HTTP_WORKER_COUNT=1    # number of HTTP workers (0 = one per CPU core)
SCONCUR_HTTP_PORT=28080        # internal port the workers listen on (nginx upstream)
SCONCUR_HTTP_PANEL_PORT=28081  # master telemetry panel (0 = off)
SCONCUR_HTTP_ADMIN_TOKEN=      # panel bearer token; empty = panel off and no runtime dashboard
SCONCUR_PANEL_HOST=http://workers:28081/api/stats  # panel stats URL as seen from the app
```

`frontend/.env`:

```dotenv
BACKEND_URL=http://localhost:8097  # nginx in front of the SConcur HTTP server; see the port in .env → APP_PORT
```

### Setup

```bash
make setup
```

### Create a user

```bash
make art c=user:create
```

### Create a service

```bash
make art c=service:create
```

### Runtime commands

```bash
make sconcur-status   # status of the sconcur PHP extension
make sconcur-restart  # stop the master; supervisor starts it back up with the fresh code
make sconcur-update   # update sconcur/sconcur: require → rebuild the image → dump-autoload → recreate containers
make deploy-prod      # pull, rebuild, install dependencies, migrate, rebuild the receiver and the frontend
```

The `sconcur.so` extension is baked into the image from `composer.lock`, so `vendor/` and the extension must be brought into step before any long-lived process starts on them. That is why both the deploy and the update targets build the image first, install dependencies from it, and only then recreate the containers — the old ones keep serving until the moment they are replaced.

### OpenAPI schema

```text
storage/api/json-schemes/traces-api-openapi-scheme.json   # trace ingestion/reading API
storage/api/json-schemes/admin-api-openapi-scheme.json    # web-panel API (search, tree, charts, dashboards)
```
