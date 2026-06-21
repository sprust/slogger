# AI Guide for SLogger

Centralized source of instructions for AI assistants (Claude Code and others) working with this repository. It merges the previously separate `CLAUDE.md`, `AGENTS.md`, and the helper agent definitions. The root `CLAUDE.md` and `AGENTS.md` now point to this file.

> **IMPORTANT:** These instructions override default behavior — follow them exactly.

---

## Approval Policy

- Before making any changes (even trivial ones), show the user a plan describing what files and lines will be modified.
- Wait for explicit user approval before proceeding with the implementation.

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
- Backend is a Laravel 12 application on PHP 8.4, running on RoadRunner via Laravel Octane.
- Frontend lives in `frontend/` and is a separate Vue 3 + Vite + TypeScript app.
- Receiver is a standalone Go service in `servers/receiver/` that accepts trace payloads over TCP.
- Storage: MongoDB (traces/logs) + MySQL (users/services/auth) + Redis/RabbitMQ (queues).
- Main orchestration is done through the root `makefile` and Docker Compose.
- Domain code is organized mostly in `app/Modules`, while Eloquent models remain in `app/Models`.

HTTP request path: **Route → Controller → Domain Action/Service → Repository → Model**

Domain events are emitted for flows that trigger queues or framework side effects; listeners in `Infrastructure/Listeners/` handle the infrastructure side.

---

## Directory Structure

- `app/Console` — artisan commands, cron commands, local utilities, make-style generators, migration helpers, Octane commands.
- `app/Http` — HTTP controllers and middleware.
- `app/Models` — Laravel/MongoDB models grouped by bounded area such as `Logs`, `Services`, `Traces`, `Users`.
- `app/Modules` — modular business code. Current modules include `Auth`, `Cleaner`, `Dashboard`, `Logs`, `Service`, `Trace`, `User`, plus shared/support modules such as `Common` and `Tools`.
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

---

## Typical Request Flow

- Route → HTTP controller → module `Domain` action/service → repository → model/storage.
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
- `Service` — service domain logic and service-related operations.
- `Tools` — technical/support infrastructure helpers.
- `Trace` — trace ingestion, aggregation, storage, and trace-oriented APIs.
- `User` — user-related domain logic and operations.

---

## Module Layering Rules

Source of truth: `code-analyse/deptrac-layers.yaml`.

### Typical Module Tree

Typical module root: `app/Modules/<ModuleName>`.

```text
app/Modules/<ModuleName>/
├── Domain/
│   ├── Actions/
│   ├── Services/
│   └── Exceptions/
├── Entities/
├── Parameters/
├── Repositories/
│   ├── Dto/
│   └── Services/
├── Infrastructure/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Services/
│   └── Commands/
└── Enums/
```

Notes:
- Not every module contains every directory. Minimal modules may have only part of this tree.
- Shared/support modules may also include directories such as `Helpers`.
- HTTP entrypoints for a module are usually placed in `Infrastructure/Http`.
- Console or framework-facing adapters should go into `Infrastructure/...`, not into `Domain`.
- Keep business use cases in `Domain/Actions`, domain services in `Domain/Services`, and transport/input objects in `Parameters`.
- Repositories are part of the declared deptrac layer and may contain DTOs or helper services related to persistence/mapping.

### Declared Layers

- `Domain` — `app/Modules/<Module>/Domain`
- `Entities` — `app/Modules/<Module>/Entities`
- `Parameters` — `app/Modules/<Module>/Parameters`
- `Repositories` — `app/Modules/<Module>/Repositories`
- `Infrastructure` — only nested directories inside `app/Modules/<Module>/Infrastructure/<Subdir>`
- `Models` — `app/Models`

### Allowed Dependencies

- `Domain` may depend only on `Entities`, `Parameters`, `Repositories`.
- `Parameters` may depend only on `Entities`.
- `Repositories` may depend only on `Entities`, `Parameters`, `Models`.
- `Infrastructure` may depend only on `Domain`, `Parameters`, `Entities`.
- `Models` must not depend on other declared layers.

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

---

## Placement Rules

- Put new business logic into the relevant module before considering shared folders.
- Do not place domain logic into controllers, requests, resources, or console commands.
- Put framework adapters, controllers, requests, resources, console integrations, and transport glue into `Infrastructure`.
- Put persistence DTOs and mapping helpers near repositories.
- Use `Common` only for genuinely shared cross-module code, not as a default dump folder.

## Test Navigation

- Module-oriented tests live under `tests/Modules`.
- When changing a module, first check for an existing matching subtree in `tests/Modules/<Module>`.
- Required post-change commands do not replace targeted test selection; use both when source changes justify it.

## Change Rules

- Treat `code-analyse/deptrac-layers.yaml` as an architectural constraint, not as documentation-only. New code in `app/Modules` must fit the declared layers or the deptrac config must be explicitly updated.
- If a module needs infrastructure code, place it inside a nested subdirectory of `Infrastructure`. Deptrac does not collect files directly under `app/Modules/<Module>/Infrastructure`.
- Changes in backend business logic should usually stay inside the relevant module instead of leaking into controllers or unrelated services.
- `frontend/src/api-schema` is generated code. Prefer regenerating it instead of editing it manually.
- Domain events are domain logic. If a flow needs to trigger queues or other framework integrations, emit a domain event first and handle infrastructure side effects in listeners.
- For Laravel jobs, prefer the `dispatch()` helper instead of static `::dispatch()`.
- Use `PSR-12` formatting for PHP changes.
- If a class is an action, it should expose a single `handle(...)` method only.
- Repositories should not contain business operations like `cancel*`; keep them as persistence primitives such as `updateStatus(...)`, and call them from actions like `Cancel*Action`.
- Each entity should have its own dedicated controller. Do not mix state/controller methods into another entity controller.

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

## Agents

Helper agent definitions for common roles. Use them as role prompts for the corresponding tasks.

### code-reviewer

> Reviews code changes after implementation. Invoke once implementation is complete — before committing.
> Tools: Read, Grep, Glob, Bash. Mode: plan.

You are a strict code reviewer for the SLogger project (Laravel 12 / PHP 8.4, Vue 3 / Vite / TS).

**What to Check:**

1. **Architecture (deptrac)**
   - Domain depends only on Entities, Parameters, Repositories
   - Infrastructure depends only on Domain, Parameters, Entities
   - Repositories depend only on Entities, Parameters, Models
   - Business logic has not leaked into controllers, requests, or commands

2. **Code Rules**
   - Action classes expose only a `handle(...)` method — no extra public methods
   - Repositories contain only persistence primitives (not `cancelOrder`, but `updateStatus`)
   - Each entity has its own dedicated controller — no mixing
   - New Laravel jobs use `dispatch()`, not the static `::dispatch()`
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
