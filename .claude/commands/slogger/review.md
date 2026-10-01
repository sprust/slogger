---
name: "SLogger: Review"
description: "Review the current branch against master by the project rules"
argument-hint: "[base or focus, e.g.: origin/feature/x | receiver only | with analysers]"
---

Review the changes of the current branch. Arguments: `$ARGUMENTS` — the base to compare against (`origin/master` by default) and/or the focus of the review.

This command does not restate the project rules. They live in `.ai/README.md` (including the `code-reviewer` agent checklist under "Agents"), `code-analyse/deptrac-layers.yaml`, the specs in `openspec/specs/**`, and the implementation notes in `README.md` / `README.ru.md`.

## Mode

- Read only. Do not edit, format or commit code, do not switch branches, do not run `git fetch`/`pull` unless told to.
- Fix only after an explicit "do it" for specific items. A question about a finding is a question, not an order to fix it.
- Report style: direct, no softening, no praise for routine work. Write the report in the language the user talks to you in.

## Step 1. Context

1. Read `.ai/README.md` in full.
2. Take the base from the arguments or use `origin/master`. Collect the changes:
   - `git merge-base HEAD <base>` → `git diff <merge-base>...HEAD --stat` and the full diff;
   - `git log --oneline <merge-base>..HEAD` — the commit messages are under review too;
   - uncommitted work: `git status`, `git diff`, `git diff --cached`, untracked files — review them as well and mark them separately.
3. For the touched paths, read in full:
   - `app/Modules/**`, `app/Models/**`, `routes/**` — the `.ai/README.md` sections "Module Layering Rules" (with "Cross-Module Dependencies"), "Placement Rules", "Change Rules", "PHP Coding Conventions"; `code-analyse/deptrac-layers.yaml`;
   - `app/Modules/Mcp/**` — the "MCP Server" section and the `openspec/specs/mcp-*` specs;
   - `app/Modules/Trace/**`, `app/Services/Clickhouse/**` — the `openspec/specs/trace-storage` spec and the README sections "Traces table (ClickHouse)", "Flexible filtering by trace data";
   - `database/migrations/**` — the "Migrations" section;
   - `frontend/**` — the "Frontend Conventions" section;
   - `servers/receiver/**` — the "Receiver Service" section and the README sections "Buffer → write to ClickHouse", "Trace timeline", "Socket protocol", "Trace message format";
   - coroutine code (HTTP, queues, pool tasks) — the README section "SConcur runtime: a request per fiber";
   - open changes in `openspec/changes/*` and plans in `.ai/plans/*` on the same subject, if any.
4. Do not review the diff blind: open whole files, look at the callers, interfaces, neighbouring implementations, and the migrations and models the code relies on.

A large diff (dozens of files, several modules, or backend + receiver + frontend) — split the reading by area between subagents, and verify the final findings yourself.

## Step 2. Review

Every rule of `.ai/README.md`, the `code-reviewer` checklist and the touched specs is a checklist item. Go through their sections in order and hold the diff against them; do not rely on memory of the rules.

Order of attention:

1. Correctness:
   - logic bugs, races, data and migrations, access rights, leaks of sensitive data;
   - SConcur: request state kept in a static or a singleton instead of `SConcur\Context\Context`; a coroutine switch inside a transaction on the PDO `mysql` connection; the row count of an `UPDATE` on `sconcur_mysql` (matched, not changed); a blocking `sleep()` or I/O that bypasses the SConcur drivers;
   - ClickHouse: values only as `{name:Type}` parameters, never in the SQL text; `FINAL` wherever the latest version of a row is needed;
   - receiver: a trace is neither lost nor counted twice on the way socket → buffer → ClickHouse (a buffer record is deleted only after its insert; attempts and `invalidBuffer`; context cancellation on shutdown), goroutine races;
   - unregistered jobs/events/listeners, a new MCP tool missing from `McpServiceProvider::TOOLS`.
2. Architecture and contracts: layers and deptrac, cross-module edges not listed under "Cross-Module Dependencies", actions with `handle(...)` only, repositories with persistence primitives only, entities and DTOs without behaviour, types, exceptions and `@throws`.
3. Tests: the changed behaviour is covered (`tests/Modules/<Module>`, for the receiver `*_test.go` next to the package), a test checks what it claims, not only the happy path.
4. Docs and generated files: `README.md` and `README.ru.md` changed together; the specs in `openspec/specs` match the behaviour; OpenAPI and `frontend/src/api-schema` regenerated rather than edited by hand; the frontend build.
5. Git: commit messages in English, subject up to 120 characters, body up to 500, no trailer but `Co-Authored-By` with the current model version.
6. Style that cs-fixer does not catch (`.ai/README.md` "PHP Coding Conventions", "Frontend Conventions", "Documentation Style").

## Step 3. Verify the findings

- Confirm every finding in the code: open the place, follow the calls. Nothing unverified goes into the report; mark something "not verified" only when it cannot be checked, and say why.
- A violation that was already in `<merge-base>` and that the branch does not touch is not a finding — it goes to the legacy section only.
- Do not run the analysers unless the arguments ask for it explicitly. If you run them, quote the real output. Commands: `make code-analise-stan`, `make code-analise-deptrac`, `make code-analise-cs-fixer-check`, `make test` (all of them — `make check`); receiver — `docker-compose exec receiver go vet ./...` and `docker-compose exec receiver go test ./...`.

## Step 4. Report

```
## Review <branch> vs <base> (<N> commits, <M> files)

### Blockers
1. `app/Modules/Trace/Domain/Actions/SomeAction.php:42` — the problem.
   Why it matters: how it breaks, or the rule it violates with a reference to the `.ai/README.md` section or the spec.
   Fix: what exactly to do.

### Important
2. ...

### Minor / style
...

### Legacy next to the changes
...

### Check by hand
- the required commands from `.ai/README.md` "Required Commands After Changes" that the diff calls for (`make check`, `make frontend-npm-build`, `make oa-generate`), and the receiver tests if it is touched.
```

- Every finding — the full path from the project root and the line number.
- Number the findings once through the whole report, not per section: the numbering goes on from one section to the next, so every finding has its own number to refer to.
- Within a section — by severity. Leave out empty sections.
- The same violation in several places — one item with the list of places.
- No findings — one line; do not invent remarks to fill space.
- End with one line: what to fix first. Then wait for instructions.
