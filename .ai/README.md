# AI Guide for SLogger

Centralized source of instructions for AI assistants (Claude Code and others) working with this repository. It holds the project guidance and the helper agent definitions; the root `CLAUDE.md` and `AGENTS.md` point here.

> **IMPORTANT:** These instructions override default behavior — follow them exactly.

---

## Required Rules And Guides

Read [rules.md](rules.md) before editing code. It contains the coding rules
transferred from PMS, including vertical arrays and named arguments. Read relevant
[tests](skills/tests.md), [repositories](skills/repositories.md),
[admin](skills/admin.md) and
[documentation](skills/documentation.md) guides before work in those areas.
[PMS import](pms-import.md) records the selected reusable rules and SLogger adaptations.
Existing authorization in the conversation applies to the ongoing task.

---

## Approval Policy

- Before making any changes (even trivial ones), show the user a plan describing what files and lines will be modified.
- Wait for explicit user approval before proceeding with the implementation.
- Every commit needs its own approval, and so does every push. An approval given for one
  commit covers that commit and nothing after it. "Commit and push, carry on" approves the
  commit named at that moment and permits the work that follows — it does not approve the
  commits that work will produce. Prepare the message, show what is staged, and ask again.
- An approval is never inherited: not from the previous commit, not from a series of them,
  not from the user having approved every one so far. The moment the work is ready to
  commit, stop and ask, however many times that has already happened in the session.

## Permission Policy For Command Execution

- At the start of work on a task, immediately request execution rights for `make check`, `make frontend-npm-build`, and `make oa-generate` if they have not already been granted in the session, even if it is not yet certain that each command will be needed. This is required so the agent can continue working unattended without pausing later for approval.
- Prefer asking for approval with a scoped command prefix that matches the command being requested.
- Do not skip these commands silently when relevant source code was changed. If approval is denied or execution fails, report that explicitly.

---

## Key Commands

```bash
# Run all code analysis + tests (required after backend changes)
make check

# Run PHPUnit tests only
make test

# Run a specific test file
make test c=tests/Modules/Trace/Repositories/Services/TraceDataToObjectBuilderTest.php

# Static analysis only
make code-analise-stan

# Architecture layer validation
make code-analise-deptrac

# Code style check / fix
make code-analise-cs-fixer-check
make code-analise-cs-fixer-fix

# Rebuild frontend assets (after frontend/src changes)
make frontend-npm-build

# Regenerate OpenAPI schema (after routes, controllers, resources, or contract enum changes)
make oa-generate

# Run an Artisan command
make art c="migrate"

# Start / stop Docker services
make up
make stop
```

---

## Project Overview

- SLogger is an observability/logging application: it collects traces and logs, stores and aggregates trace data, exposes admin/API endpoints for browsing traces and logs, provides dashboard views, and includes cleanup tooling.
- Backend is a Laravel 12 application on PHP 8.4, running on SConcur — a concurrent coroutine HTTP runtime that executes each request in its own PHP Fiber inside a single long-lived process.
- Frontend lives in `frontend/` and is a separate Vue 3 + Vite + TypeScript app.
- Receiver is a standalone Go service in `servers/receiver/` that accepts trace payloads over TCP.
- Storage: ClickHouse (traces, one `traces` table over the HTTP interface, `app/Services/Clickhouse`) + MongoDB (the receiver's buffer, counters, tree cache, incidents, notifications) + MySQL (users/services/auth) + RabbitMQ (queues) + Redis (cache, through the `sconcur` client and the `sconcur_redis` store).
- Main orchestration is done through the root `makefile` and Docker Compose.
- Domain code is organized mostly in `app/Modules`, while Eloquent models remain in `app/Models`.

HTTP request path: **Route → Controller → module UseCase → Actions/Services → Repository → Model**

Domain events are emitted for flows that trigger queues or framework side effects; listeners in `Infrastructure/Listeners/` handle the infrastructure side.

---

## Directory Structure

- `app/Console` — artisan commands, cron commands, local utilities, make-style generators, migration helpers.
- `app/Http` — HTTP controllers and middleware.
- `app/Models` — Laravel/MongoDB models grouped by bounded area: `Mcps`, `Notifications`, `Services`, `Traces`, `Users`, `Watchers`.
- `app/Modules` — modular business code. Current modules include `Auth`, `Cleaner`, `Dashboard`, `Logs`, `Mcp`, `Notification`, `Service`, `Trace`, `User`, `Watcher`, plus shared/support modules such as `Common` and `Tools`.
- `app/Providers` — Laravel service providers.
- `app/Services` — cross-cutting services outside module folders, including logging integrations.
- `code-analyse` — static analysis and architecture rules: PHPStan, PHP CS Fixer, Deptrac.
- `frontend/src` — frontend source code: page components, shared components, store, utilities, generated API schema.
- `routes` — Laravel route definitions.
- `tests` — backend tests, including module-oriented tests.
- `packages` — local path Composer packages used by the application.
- `servers/receiver` — receiver-specific service files and environment.
- `storage/api` — generated OpenAPI artifacts and related files.

---

## Receiver Service

- `servers/receiver` is a separate Go service, not a Laravel submodule.
- Its purpose is trace intake: it accepts incoming trace payloads over TCP socket, runs a transporter pipeline, and writes internal runtime stats to `servers/receiver/storage/stats.json`.
- In Docker it runs as the `receiver` service from `docker-compose.yml` and starts the binary `/app/bin/receiver`.
- It uses its own environment file mounted from `servers/receiver/.env`.
- Receiver data and logs are kept under `servers/receiver/storage`.

### Receiver Tree

- `servers/receiver/cmd/receiver/main.go` — main binary entrypoint. Loads `.env`, starts socket server and transporter, handles shutdown signals, and periodically saves runtime stats.
- `servers/receiver/cmd/stats/main.go` — local stats viewer that reads `storage/stats.json` and refreshes it in terminal.
- `servers/receiver/cmd/traces-migrate` — single-use migration of an installation that stored traces in MongoDB: moves the `tracesPeriodic` hourly collections into the ClickHouse `traces` table newest hour first, deleting each batch from MongoDB once ClickHouse has it. Built with the receiver (`make migrate-build`), run by `make traces-migrate-mongo-clickhouse`.
- `servers/receiver/cmd/loadgen` — load generator for stress tests: sends generated trace trees through the receiver socket (a share of them with the update before the create, the create twice, or no update), pauses while the MongoDB `buffer` holds more than `-buffer-max` documents, and writes progress to `-progress-file` as JSON.
- `servers/receiver/internal/dto` — DTO definitions for incoming auth and trace messages.
- `servers/receiver/makefile` — local build/run commands for the Go service and stats binary.
- `servers/receiver/go.mod` and `servers/receiver/go.sum` — isolated Go module definition and dependencies.
- `servers/receiver/storage` — receiver-local persistent files such as logs and generated stats.

### Receiver Development Notes

- Receiver has its own technology stack and lifecycle. Treat it as a separate executable service that integrates with the main application.
- If a task touches trace ingestion format, socket protocol, or receiver transport behavior, inspect `servers/receiver` together with Laravel trace modules.
- Generated or cached local artifacts like `servers/receiver/.gocache` and IDE files are not project source of truth.
- Receiver-specific changes that affect runtime behavior should be treated as source changes, even though they are outside `app/`.

---

## Fast Entry Points

- Backend HTTP routes: `routes/admin-api.php`, `routes/api.php`, `routes/web.php`.
- Backend HTTP controllers: `app/Http/Controllers` and `app/Modules/*/Infrastructure/Http/Controllers`.
- Backend requests/resources: `app/Modules/*/Infrastructure/Http/Requests` and `app/Modules/*/Infrastructure/Http/Resources`.
- CLI entrypoints: `app/Console/Commands` and `routes/console.php`.
- Module business logic: `app/Modules/*/Domain`.
- Persistence and mapping: `app/Modules/*/Repositories` and `app/Models`.
- Frontend pages: `frontend/src/components/pages`.
- Frontend state and helpers: `frontend/src/store`, `frontend/src/utils`.

## MCP Server

The `Mcp` module serves the MCP protocol (revision `2026-07-28` only, Streamable HTTP, JSON
responses, no sessions, no SSE) at `POST /mcp` (`routes/mcp.php`), outside the admin API.

- `Infrastructure/Http/Middlewares/McpOriginMiddleware` rejects a foreign `Origin` with `403`;
  `McpTokenMiddleware` accepts the Bearer token of an enabled connection from the `mcps` table
  and in `terminate` adds one to `requests_count` and sets `last_used_at`, in one `UPDATE`.
- `Infrastructure/Http/Controllers/McpEndpointController` → `Infrastructure/Protocol`: the
  message parser, the header and version validators, and `McpServer`, which dispatches
  `server/discover`, `tools/list`, `tools/call`, `prompts/list`, `prompts/get`. Protocol
  errors are `McpProtocolException`; a tool's own failure is a result with `isError: true`. A `ClickhouseQueryException` thrown by a tool is reported and answered as the tool error `query_failed`.
- `Infrastructure/Tools/*Tool` — one class per tool. A tool validates nothing by hand: its
  `McpToolSchema` is compiled both into the JSON Schema of `tools/list` and into the
  validator rules. A tool calls only this module's bridges (`Domain/Actions/Bridges`), never
  another module. New tools are registered in `McpServiceProvider::TOOLS`; the order there
  is the order of `tools/list`.
- Tools that query traces over a period: `GetTraceFacetsTool`, `SearchTracesTool`,
  `GetTraceDataFieldsTool`, `AggregateTracesTool`, `CompareTraceGroupsTool`. They read
  `service_ids` (optional), `from`, `to` through `McpToolTraceScopeReader`: the period is taken
  exactly and is at most `cleaner.lifetime_hours` long (`McpTracePeriodResolver`, bound in
  `McpServiceProvider`). `data_filter` of `search_traces` and `aggregate_traces` is parsed by
  `McpTraceDataFilterParser`. The groups and the comparison are one aggregation over the traces
  table (`Trace\Repositories\TraceGroupsRepository`).
- `SearchSloggerLogsTool` reads the logs of SLogger itself through the `Logs` module.
- `Infrastructure/Prompts/*Prompt` — the prompts, registered in `McpServiceProvider`.
- `resources/mcp/instructions.md` — the instructions the model gets, in English.
- With `SLOGGER_LOG_REQUESTS_ENABLED` `/mcp` is traced like the admin API. `App\Services\SLogger\RequestWatcher`
  adds the JSON-RPC method and `params.name` to the tags of a successful answer; the answer
  body is not recorded (`mcp` in the output `hidden_paths` of `config/slogger.php`).
- Connections are managed through `/admin-api/mcps` and the `/mcps` page.

---

## Typical Request Flow

- Route → HTTP controller → module `Domain/UseCases` → Actions/Services → repository → model/storage.
- Request validation and API resources usually live in module `Infrastructure/Http`.
- Framework-specific adapters should stay in `Infrastructure`; business logic should stay in `Domain`.
- UI-driven changes are usually traced through `frontend/src/components/pages` → `frontend/src/api-schema` → backend route/controller → module domain code.

---

## Module Responsibilities

- `Auth` — authentication-related behavior.
- `Cleaner` — cleanup workflows and cleanup orchestration.
- `Common` — shared entities, enums, helpers, shared HTTP resources.
- `Dashboard` — dashboard and aggregated views.
- `Logs` — log browsing and related read models.
- `Mcp` — the MCP server: connections (`mcps`), the protocol endpoint `/mcp` and the read-only tools LLM clients call.
- `Service` — service domain logic and service-related operations.
- `Tools` — technical/support infrastructure helpers.
- `Trace` — trace ingestion, aggregation, storage, and trace-oriented APIs.
- `User` — user-related domain logic and operations.

---

## Module Layering Rules

Architecture is checked in two dimensions by `code-analyse/deptrac-layers.yaml`
(layers) and `code-analyse/deptrac-modules.php` (module ownership). Both run through
`make code-analise-deptrac`. `make code-analise-modules-structure` checks directory
placement; custom rules in `code-analyse/PHPStan` run through `make code-analise-stan`.
All checks are part of `make check`; do not add a baseline to hide violations.
Regression fixtures live in `tests/Architecture`.

A module may contain `Domain/{Actions,Services,UseCases,Events,Exceptions}`,
`Entities`, `Parameters`, `Enums`, `Helpers`, `Transports`, `Repositories/{Dto,Services}` and
`Infrastructure`. Infrastructure includes HTTP, commands, jobs, listeners, tasks,
services, broadcasting, protocol, tools, prompts and exception managers. Unused
folders need not exist. Module providers live in the root of Infrastructure; their base
lives in `Common/Infrastructure/Providers`. Both are
included in analysis, as are Common and enums.

- Actions and Services use their module's repositories and data. Actions expose
  one public business method, `handle`. Use ordinary constructor DI.
- UseCases orchestrate Actions and Services, expose `handle`, and do not call
  repositories or other UseCases. Business HTTP endpoints call one local UseCase;
  protocol parsing/routing remains in Infrastructure.
- Entities and Parameters hold local or common data types, including nested
  fields, collections and enums. Foreign DTOs must be converted to local types by module `Transports/*Transport`,
  called from Domain. Do not bypass this rule with inheritance, aliases, `mixed` or untyped arrays.
- Actions/UseCases accept local Parameters. Domain may construct foreign Parameters
  immediately before an allowed foreign Action call. PHPStan checks input types,
  including aliases, unions, generics, inherited and nested DTOs.
- Transports convert typed Entities/Parameters/Enums along the declared module graph;
  they may use their own repositories but never foreign Domain, Repositories or
  Infrastructure. Domain/UseCases/Repositories use local Transports; Entities,
  Parameters, Enums and Infrastructure do not. Source Object inputs are allowed
  only in Transports, not public Domain business methods.
- Repositories perform persistence primitives and mapping, use their owned Models,
  and do not call another repository. Repository mapping services/DTOs remain allowed.
  Public results are data objects or primitives, never Model/Builder instances.
  Physical deletion remains supported.
- Infrastructure calls its own Domain, uses its own data and Common infrastructure.
  Foreign Events/Exceptions are integration contracts, not access to foreign business
  Actions. Providers bind their own layers and Common contracts; global Laravel
  providers and ModulesConfig are composition points.
- Enums do not access repositories/adapters. Common has no reverse dependencies on
  business modules. Domain does not construct HTTP responses.

Only Domain Actions/Services may use foreign Domain/data, along these directions:

| Source | Foreign modules |
| --- | --- |
| Auth | User |
| Cleaner | Trace, Setting |
| Logs | Setting |
| Trace | Service |
| Watcher | Trace, Logs, Auth |
| Notification | Watcher |
| Mcp | Service, Watcher, Trace, Logs, Setting |

Other modules have no foreign business dependencies by default. New modules are
collected automatically and require an explicit edge. Providers, Infrastructure
and UseCases may use the Events/Exceptions of their declared integrations, but
not foreign Entities, Parameters or services. There are no MCP bridge exceptions.

Model ownership is explicit in the module config. In particular,
`Models/Traces/TraceMetric` belongs to Dashboard and
`Models/Traces/TraceClearingProcess` belongs to Cleaner. Shared abstract Models
belong to Common. Models do not depend on module enums; repositories map storage
values to domain types.

---

## Application Dispatcher

`Common/Domain/Services/DispatcherInterface` is the application boundary for
`event(object)`, `dispatch(ShouldQueue)`, `report(Throwable)` and
`registerJobs(array<class-string<ShouldQueue>>)`. Its singleton implementation
forwards calls immediately through Laravel event/bus/exception contracts. It
keeps only the boot-time set of job classes, never request payload or routing state.

Module providers bind services in `register()` with their existing lifetimes.
During `boot()`, `getJobs()` registers application jobs idempotently and
`getListeners()` attaches local listeners. The five jobs are Trace build/delete
cache, Notification send, Cleaner clear traces and Dashboard refresh statistics.
Global EventServiceProvider does not duplicate these mappings. Package jobs are
not intercepted. Commands remain registered by their owner module.

A job's connection overrides `queue.default`; its queue overrides the chosen
connection's configured queue, with `default` as fallback. A sync driver is
allowed without RabbitMQ topology. Async routing requires the configured
`sconcur.queue.rabbitmq.connection`, driver `sconcur_rabbitmq`, and membership in
`sconcur.queue.rabbitmq.queues`. Worker groups and running consumer counts are not
an allow-list. Unknown jobs/routes are skipped and logged directly with the class
and route, without payload or recursive report. Transport exceptions propagate;
the dispatcher does not retry. The same job object reaches Laravel, preserving
delay, after-commit, retries and backoff. No PendingDispatch destructor is involved.

`report` passes the original exception to the existing Handler, including its
expected-error filters. No PMS reporter wrapper or Horizon dependency is added.
Immediate trace broadcasts stay immediate and retain their existing rescue policy.
The two scheduled application jobs use named callbacks through the dispatcher;
frequencies and the separate scheduled commands/SConcur tasks remain unchanged.

---

## Source Boundaries

- Backend source code that affects behavior usually lives in `app/**`, `routes/**`, `database/**`, and local packages in `packages/**`.
- Frontend source code that affects behavior usually lives in `frontend/src/**` and `frontend/public/**`.
- HTTP-layer changes usually include `app/Http/**`, `routes/**`, and `app/Modules/*/Infrastructure/Http/**`.
- Enum changes that may affect contracts usually include `app/Modules/*/Enums/**` and shared enums in `Common`.

## Generated And Non-Source Files

- `frontend/src/api-schema/**` is generated from OpenAPI. Prefer regeneration over manual edits.
- `storage/api/**` contains generated API artifacts.
- Documentation and operational files such as `*.md` and `.env*` are not treated as source changes unless they alter committed executable behavior.
- `.claude/commands/opsx/**` and `.claude/skills/openspec-*/**` are generated by the OpenSpec CLI. Refresh them with `openspec update` instead of editing by hand.

---

## Placement Rules

- Put new business logic into the relevant module before considering shared folders.
- Do not place domain logic into controllers, requests, resources, or console commands.
- Put framework adapters, controllers, requests, resources, console integrations, and transport glue into `Infrastructure`.
- Put persistence DTOs and mapping helpers near repositories.
- Use `Common` only for genuinely shared cross-module code, not as a default dump folder.

## Documentation Style

- Verify every technical claim against the code before writing it — class and
  method names/signatures, option names and defaults, enum cases, CLI flags, file
  paths, behavioral claims. Fix inaccuracies; never guess.
- Minimal bold. Use **bold** only for a genuinely critical warning or a couple of
  key terms — not one highlight per paragraph (heavy bolding is the top
  "AI-generated" tell).
- Dry, factual tone. Short sentences; drop gratuitous decorative quotes around
  terms and marketing metaphors.
- Diagrams in Mermaid (GitHub renders them). Rules to keep them rendering
  everywhere, including PhpStorm:
    - No `<br/>` anywhere — some renderers print it literally. Use single-line
      node labels (combine ideas with ` — ` or `(...)`); in `sequenceDiagram` use
      separate `Note over` lines.
    - For a request+response between two components use one bidirectional edge
      `A <-->|"..."| B`, never two opposing edges — a 2-cycle makes the layout
      engine place the blocks side-by-side / reversed.
    - In `flowchart TB` declare the caller first so it renders on top.
    - Label edges with the real call/method names from the code.

## Test Navigation

- Module-oriented tests live under `tests/Modules`.
- When changing a module, first check for an existing matching subtree in `tests/Modules/<Module>`.
- Required post-change commands do not replace targeted test selection; use both when source changes justify it.

## Change Rules

- Treat `code-analyse/deptrac-layers.yaml` as an architectural constraint, not as documentation-only. New code in `app/Modules` must fit the declared layers or the deptrac config must be explicitly updated.
- Put adapters inside the relevant nested `Infrastructure` directory. Module providers and the base in `Infrastructure/Providers` have a dedicated composition layer; both forms are checked by Deptrac.
- Changes in backend business logic should usually stay inside the relevant module instead of leaking into controllers or unrelated services.
- `frontend/src/api-schema` is generated code. Prefer regenerating it instead of editing it manually.
- Domain events are domain logic. If a flow needs to trigger queues or other framework integrations, emit a domain event first and handle infrastructure side effects in listeners.
- Send application jobs through `DispatcherInterface::dispatch()`; register their classes in the owning module provider.
- Use `PSR-12` formatting for PHP changes.
- If a class is an action, it should expose a single `handle(...)` method only.
- Repositories should not contain business operations like `cancel*`; keep them as persistence primitives such as `updateStatus(...)`, and call them from actions like `Cancel*Action`.
- Each entity should have its own dedicated controller. Do not mix state/controller methods into another entity controller.
- The main README is bilingual: `README.md` (English) and `README.ru.md` (Russian), kept in sync via the language switcher line at the top of each. Always edit both language versions together — any change to one must be mirrored in the other so they never drift.

### PHP Coding Conventions

- All traits must be named with a `Trait` postfix (e.g. `HasFactoryTrait`), and the file name must match the trait name (PSR-4).
- Do not use the `final` keyword on classes. Keep classes extendable.
- Do not declare global/namespaced helper functions (no `function current_context()` style API). Expose behavior through classes and static entry points instead (e.g. `SConcur\Context\Context::current()`).
- Name the cases of a status enum in the past tense — `Opened`, `Closed`, `Finished`,
  `Canceled`. A status says what happened to the record, not what it currently looks like,
  and a set mixing an adjective with a participle (`Open` beside `Closed`) reads as two
  different kinds of thing.
- Entities, DTOs and parameter objects hold data and nothing else: promoted readonly
  properties and a constructor. No behaviour methods on them, and no static factory or
  named-constructor methods (`SomeObject::delivered()`, `::fromArray()`) — build them with
  `new` and keep the decision about what to build in the action or service that makes it.
  The settings objects' `toArray()` is the one exception: it is how a settings column is
  written, and the object is the only place that knows its own fields.
- Timestamps come last, in this order: `createdAt`, `updatedAt`, `deletedAt` (`created_at`,
  `updated_at`, `deleted_at`). That holds for entity and DTO constructors, model `@property`
  blocks, migration columns, repository parameters and the documents a repository writes.
  A timestamp that is part of what the record means — `sentAt`, `occurredAt`, `closedAt` —
  is an ordinary field and stays with the others, before the three above.
- Call with named arguments once a call is written across more than one line —
  `new HttpClient(responseFactory: ..., options: ...)`, `$sender->send(channel: ..., text: ...)`.
  Use named arguments for multiple arguments or optional parameters, as specified in `rules.md`. Named arguments bind to parameter names, so
  the names of an interface's parameters become part of its contract, and the same goes
  for a third-party constructor: renaming a parameter is then a breaking change.
- A call passed as an argument to another call goes on its own line:

  ```php
  return ChannelResource::collection(
      $this->findChannelsAction->handle()
  );
  ```
- Do not interpolate property or method access into a string — no `"channel [$dto->id]"`.
  Use `sprintf()` with placeholders. A plain scalar variable may still be interpolated:
  `"channel [$channelId] not found"`.
- Give every string validation rule a `min` beside its `max`
  (`['required', 'string', 'min:10', 'max:255']`). The generated OpenAPI schema carries
  both as `minLength`/`maxLength`, so a rule changed here needs `make oa-generate`.
- For SConcur coroutine state, use the library's `SConcur\Context\Context` (`Context::current()->find/has/set/forget`) — do not reimplement a context store. Working-with-context semantics: `vendor/sconcur/sconcur/docs/coroutine-context.ru.md`.

### Frontend Conventions

- Nothing on the page may change its own size. A control that appears on hover, a label
  that shows up only while something is searching, a spinner that replaces a button's
  text, a counter that grows from empty — each of them moves everything beside it, and
  the layout twitches while the user is reading or aiming at a button.
- Keep the space instead of the element: `visibility: hidden` rather than `display: none`,
  a fixed `min-width` on a slot whose text comes and goes, a placeholder of the same size
  as what will replace it. Something that has nothing to say says it as empty space, not
  by collapsing.
- The same goes for lists and rows: a row's controls are laid out once, whether they are
  shown or not, so a table does not re-flow when the pointer crosses it.
- Do not set a font size. Element's scale is `14px` base, `13px` small, `12px` extra
  small, and a component sits on it by inheriting — a `font-size` written by hand makes
  one block quietly smaller than everything around it. Change one only when the task
  asks for it in those words, and then say so in the report.

### Landing Page

The public page at `/` (`frontend/src/components/pages/landing`) describes the system in
words and shows it with the panel's own views on demo data. It goes stale silently: nothing
fails when the system changes and the page does not. So any change to what it describes or
to what it reuses includes checking it:

- What it describes: trace intake and the socket protocol (message format, ports, the
  receiver, the buffer, pending traces, ClickHouse), search and data filters, graphs, intake
  metrics, watchers and notification channels, the MCP tools and prompts (names and
  parameters in `content/mcpScenarios.*.ts`), the runtime and stores, cleanup, installation
  (`make` commands, env variables, default ports). When any of it changes, update the texts
  in `content/en.ts` and `content/ru.ts` together — the two languages never drift apart —
  and the demo data in `demo/` if its shape follows the changed format.
- What it reuses: the props-only views of the admin (`TraceTreeRowView`,
  `TraceTracesTableView`, `TraceDetailView`, `TraceAggregatorTraceDataNode`,
  `TraceAggregatorTracesCustomFields`, `FilterTagsBarView`, `FilterTagsSection`,
  `TraceTimelineChart`, `TraceMetricsChart`, `IncidentEventsTable`, `WatcherListView`) and the
  helpers beside them (`customFields.ts`, `traceMetricsValues.ts`, `traceTimelineSeries.ts`,
  `incidentEventColumns.ts`, `IndicatorSetter`, `TreeJsonBuilder`). A change to their props,
  events or data shape is a change to the landing too: open `/` and check the section that
  uses it.
- Apart from the sign-in dialog (`LandingLoginDialog`, which uses `authStore`, the token
  storage and the router), the landing imports no stores and no API client at runtime
  (`import type` only), so it makes no requests until the visitor signs in.

### Migrations

- Name a migration the way the framework does: `Y_m_d_His_snake_case_description.php`, where
  the prefix is the time the file was created **in UTC**. Let
  `make art c="make:migration create_widgets_table"` produce it, or take the timestamp from
  `date -u +%Y_%m_%d_%H%M%S` — never invent a round one.
- UTC, not local time: `MigrationCreator::getDatePrefix()` is `date('Y_m_d_His')`, and the
  framework has already called `date_default_timezone_set(config('app.timezone'))`, which is
  `UTC` here (`config/app.php`). A prefix written in a local zone sorts ahead of migrations
  that were actually written later somewhere else.
- Round prefixes like `100000`, `100100`, `120000` are the tell of a hand-written name. They
  do not order correctly against the real ones written the same day, they make several
  migrations look like a single scripted batch, and they lose the one thing the prefix is
  for: when the change was actually written.
- A migration already committed keeps its name whatever it looks like — it may have run on
  installations, and renaming it makes the migrator run it again.

## Required Commands After Changes

- Run post-change commands only after changes that can affect application behavior, generated artifacts, or runtime contracts.
- Do not run these commands after documentation-only or operational-only edits such as `*.md`, `.env*`, and other non-code descriptive files, unless those edits also change executable behavior.
- After backend or shared application code changes, run `make check`.
- After frontend source changes, also run `make frontend-npm-build`.
- After changes to HTTP layer or enums that affect OpenAPI/contracts, also run `make oa-generate`.

### Quick Decision Rules

- If backend PHP code changed and it can affect behavior, run `make check`.
- If only docs, markdown, comments, or `.env` templates changed, do not run post-change commands by default.
- If `frontend/src` or other frontend runtime assets changed, run `make frontend-npm-build`.
- If routes, controllers, requests, resources, HTTP services, or contract enums changed, run `make oa-generate`.
- If generated files are out of sync with source changes, regenerate them instead of patching around the drift.

---

## Commit & Pull Request Guidelines

Use short, imperative subjects (`update mongodb serializer`, `remove obsolete handler
tests`). Write them in English, whatever language the conversation that produced the
change was held in — the history here is English throughout, and a message in another
language is unreadable in the middle of it.

Keep commit messages short. The subject is at most 120 characters, and the body at most
500 — a couple of short paragraphs, no more. Sign-off trailers do not count towards
either. Say what changed and why in the space that gives you; detail that does not fit
belongs in the code's own comments, in the README, or in a `.ai/plans/` file, all of
which a later reader can find from the code. A long commit message is the one place that
detail cannot be maintained, because nothing updates it when the code moves on.

Pull requests should explain the behavioral change, list the validation performed
(`make check`, `make frontend-npm-build`, targeted tests), and link the related issue or
task. Screenshots are usually unnecessary unless documentation or tooling output changed
materially.

When an AI agent creates a git commit itself, it must add a sign-off trailer identifying
the agent:

```
Co-Authored-By: <agent name> <email>
```

The name must carry the model version the commit was actually written by — read it from
the running session (for Claude Code: the model reported in the environment, e.g.
`Claude Opus 5 (1M context)`), never copy the version from an example or from an earlier
commit. The trailer is how a later reader knows which model produced the change, so a
stale version in it is misinformation.

The format, with the version standing in for whatever is current:
`Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>` for Claude Code,
`Co-Authored-By: OpenAI Codex <noreply@openai.com>` for OpenAI Codex.

That trailer is the only one. No `Claude-Session:`, no session or chat URL, no
"Generated with ..." line, in a commit message or a pull request description. They point
at a conversation nobody outside it can open, they date instantly, and the history keeps
them forever.

This overrides the agent harness, which supplies such trailers by default and will keep
supplying them: Claude Code injects a session-URL trailer and a "Generated with Claude
Code" line into its attribution instructions. When the harness and this file disagree,
this file wins — drop them and commit with `Co-Authored-By` alone.

---

## Spec Workflow (OpenSpec)

New features and behavior changes are specified with [OpenSpec](https://openspec.dev/) before implementation.

- `openspec/specs/` — the current specs of the system, one folder per capability.
- `openspec/changes/<name>/` — a proposed change: `proposal.md`, `design.md`, `tasks.md` and delta specs.
- `openspec/changes/archive/` — finished changes, merged into `openspec/specs/`.
- `openspec/config.yaml` — project context and per-artifact rules fed to every artifact. Artifacts are written in Russian.

Cycle: explore (optional) → propose → review → apply → archive. Update revises the artifacts of an open change, and sync merges its delta specs without archiving.

| Workflow | Codex skill | Claude command |
| --- | --- | --- |
| Explore | `$openspec-explore` | `/opsx:explore` |
| Propose | `$openspec-propose` | `/opsx:propose` |
| Apply | `$openspec-apply-change` | `/opsx:apply` |
| Update | `$openspec-update-change` | `/opsx:update` |
| Sync | `$openspec-sync-specs` | `/opsx:sync` |
| Archive | `$openspec-archive-change` | `/opsx:archive` |

Codex uses the generated `.agents/skills/openspec-*/SKILL.md` files, installed by
`openspec init --tools codex`. Do not add `opsx-*` wrappers for the same workflows.
Keep project-specific rules here and in `openspec/config.yaml`, not in generated skills.
Before using a workflow, read this guide and run commands from the project root.
If `openspec` is missing from PATH, check `~/.local/bin/openspec` before reporting it
uninstalled. Installing the CLI or initializing a project still requires a user request.

OpenSpec does not relax the Approval Policy: a proposal is the plan to approve, and apply starts only after explicit approval. Selecting a workflow automatically does not grant approval. Commits and pushes still need their own approval.

Existing `.ai/plans/` files stay where they are; new work goes through `openspec/changes/`.

---

## Agents

Helper agent definitions for common roles. Use them as role prompts for the corresponding tasks.

### code-reviewer

> Reviews code changes after implementation. Invoke once implementation is complete — before committing.
> Tools: Read, Grep, Glob, Bash. Mode: plan.

You are a strict code reviewer for the SLogger project (Laravel 12 / PHP 8.4, Vue 3 / Vite / TS).

**What to Check:**

1. **Architecture (deptrac)**
   - Check the layer and module matrices above and both Deptrac configurations
   - UseCases call local Actions/Services, not Repositories or other UseCases
   - Entities/Parameters use local or common data; conversions belong to Transports
   - Business logic has not leaked into controllers, requests, or commands

2. **Code Rules**
   - Action classes expose only a `handle(...)` method — no extra public methods
   - Repositories contain only persistence primitives (not `cancelOrder`, but `updateStatus`)
   - Each entity has its own dedicated controller — no mixing
   - New application jobs use `DispatcherInterface::dispatch()` and module registration
   - PSR-12 formatting

3. **Tests**
   - Tests exist for changed logic under `tests/Modules/<Module>`
   - Tests cover both happy path and edge cases

4. **Security**
   - No SQL injection, XSS, or command injection
   - Input is validated at system boundaries (HTTP requests)
   - No hardcoded secrets

5. **Generated Files**
   - If routes, controllers, requests, resources, or contract enums changed — `make oa-generate` is required
   - If frontend changed — `make frontend-npm-build` is required

**How to Get the Diff:**

```bash
git diff HEAD
git diff --name-only HEAD
```

**Response Format.** Start with `PASSED` (everything looks good, ready to commit) or `FAILED` (issues found). Then:

```
### Issues (if any)
- [file:line] Description of the problem and how to fix it

### Required Commands
- [ ] make check
- [ ] make oa-generate  (if HTTP layer was touched)
- [ ] make frontend-npm-build  (if frontend was touched)

### Notes (non-critical)
- ...
```

If `FAILED` — clearly state what needs to be fixed so implementation can resume.

### spec-writer

> Legacy: new specifications go through OpenSpec (`/opsx:propose`), see Spec Workflow above.
> Writes a technical specification before implementation. Invoke when you need to define requirements, design, and an implementation plan.
> Tools: Read, Grep, Glob. Mode: plan.

You are a technical analyst for the SLogger project (Laravel 12 / PHP 8.4, Vue 3 / Vite / TS, Go receiver). Your task is to write a clear technical specification before implementation begins.

**What to Do:**

1. Explore the affected modules in `app/Modules/<Module>/Domain`, `Repositories`, `Infrastructure/Http`
2. Review existing tests in `tests/Modules/<Module>`
3. Check routes in `routes/admin-api.php`, `routes/api.php`
4. If the feature touches the frontend — check `frontend/src/components/pages`

**Spec Structure:**

```
## Goal
What exactly needs to be done and why.

## Affected Modules and Files
List of modules, files, routes.

## Requirements
- Functional requirements
- Non-functional requirements (performance, security)

## Solution Design
- New classes/methods (Action, Repository, Controller, etc.)
- Changes to existing classes
- Deptrac layers: Domain / Entities / Parameters / Repositories / Infrastructure

## API Contracts (if applicable)
New or changed endpoints with request/response examples.

## Testing Strategy
What tests need to be written and where they will live.

## Out of Scope
What is intentionally excluded.
```

**Rules:**

- Do not edit code — only explore and write the spec
- Respect deptrac layers: Domain depends only on Entities, Parameters, Repositories
- Action classes expose only a `handle(...)` method
- Repositories contain only persistence primitives
- Business logic must not leak into controllers

When the spec is ready — output it and ask whether implementation can begin.

## Module Exception Managers

Each module registers its HTTP exception manager through `getExceptionManagerClass()`
on its provider. `Common/Infrastructure/Exceptions/ExceptionManagerRegistry` stores
one manager per class. The application Handler uses the registry for reporting and
rendering: mapped business errors are not reported; unknown errors follow Laravel's
normal handling. Managers preserve existing status codes and response messages.
Dashboard's unavailable cache keeps its `503` JSON body. MCP protocol and tool errors
remain in the protocol/tool handlers and do not pass through these HTTP mappings.

Business controllers call one local UseCase per operation. Read-after-write and type
guards belong to that scenario. UseCases return complete DTOs or throw domain errors;
controllers validate transport input and construct resources. The five queued job
classes retain their FQCN and payload fields; `LegacyJobPayloadTest` compares them
against serialized fixtures generated from the pre-refactor classes.

MCP bridges return local Entities and enums, including nested trace data, tree nodes,
groups, incidents and service summaries. `Mcp/Transports` converts source DTOs and
query parameters; tools do not use another module's resource or data contract.
`TraceDataParityTest` checks recursive HTTP/MCP data output, null, empty children,
scalar types, key order and untruncated long values.

## Module Provider Lifecycle

`ModulesConfig::getProviders()` is the single provider list used by `config/app.php`
and `Console/Kernel::schedule`; Common goes first. Each module declares `setSchedule`,
`getCommands`, `getJobs`, `getListeners` and `getExceptionManagerClass`, using empty
lists or null where appropriate. Module providers use `onRegister` and `onBoot`
instead of overriding Laravel's lifecycle methods.

`Common/Infrastructure/Providers/BaseServiceProvider` registers the existing SLogger
`getContracts` singletons before `onRegister` factories. At boot it calls `onBoot`,
registers listeners, console-only commands, jobs and the exception manager. It does
not cache the application or dispatcher in static properties. Scheduled callbacks
in Cleaner and Dashboard use DispatcherInterface; User and Logs own their scheduled
commands. Frequencies, names and overlap locks are preserved.
