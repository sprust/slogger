# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> Detailed architectural rules, placement rules, change rules, and post-change commands are in **AGENTS.md**. Read it before making changes.

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

## Architecture Summary

SLogger is an observability platform that ingests, stores, and surfaces traces and logs.

- **Backend**: Laravel 12 / PHP 8.4 running on RoadRunner via Laravel Octane
- **Frontend**: Vue 3 + Vite + TypeScript in `frontend/`
- **Receiver**: Standalone Go TCP service in `servers/receiver/` that accepts trace payloads
- **Storage**: MongoDB (traces/logs) + MySQL (users/services/auth) + Redis/RabbitMQ (queues)

Business logic lives in `app/Modules/<ModuleName>/` with strict layer separation enforced by Deptrac:

```
Domain/Actions  →  Queries/ and Mutations/ (CQRS-style, one handle() method each)
Domain/Services →  domain services
Repositories/   →  persistence abstractions + DTOs
Infrastructure/ →  HTTP controllers, requests, resources, console commands
Entities/       →  domain objects (not Eloquent models)
Parameters/     →  input/transport objects
```

Eloquent models live in `app/Models/`, not inside modules.

HTTP request path: **Route → Controller → Domain Action/Service → Repository → Model**

Domain events are emitted for flows that trigger queues or framework side effects; listeners in `Infrastructure/Listeners/` handle the infrastructure side.

## Tech Stack Details

- PHP 8.4, Laravel 12, PSR-12 style (enforced by PHP CS Fixer)
- PHPStan for static analysis (`code-analyse/phpstan.neon`)
- Deptrac for architecture boundaries (`code-analyse/deptrac-layers.yaml`)
- MongoDB via `mongodb/laravel-mongodb`; MySQL via standard Eloquent
- Queue backend: Redis or RabbitMQ depending on config
- `frontend/src/api-schema/` is generated from OpenAPI — regenerate, do not edit manually
