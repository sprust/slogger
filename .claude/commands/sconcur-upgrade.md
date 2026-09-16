---
description: Wait for a new sconcur/laravel release, install it and adapt the project to it on the current branch
argument-hint: "[extra instructions, e.g. create a branch first]"
---

Wait for a new release of `sconcur/laravel`, install it, and adapt this project to it.

Extra instructions from the user (may be empty):

<extra-instructions>
$ARGUMENTS
</extra-instructions>

Invoking this command is the user's approval for the whole flow below: installing the
update and changing the code needed to adapt to it. Do not stop to ask for a plan
approval between steps. It is not an approval to commit or push.

## 1. Extra instructions

Read the extra instructions first. Carry out the ones that belong at the start (for
example, creating or switching a branch) before anything else, and keep the rest in mind
for the later steps — they take precedence over the defaults below. With no extra
instructions, work on the current branch.

## 2. Starting point

- Read `.ai/README.md`; its rules apply to every change you make.
- Record the installed versions from `composer.lock`:
  `jq -r '.packages[] | select(.name=="sconcur/laravel" or .name=="sconcur/sconcur") | "\(.name) \(.version)"' composer.lock`
- Record the `sconcur/laravel` constraint in `composer.json`.
- Tell the user which version you are waiting past.

## 3. Wait for the release

Poll Packagist every 30 seconds with a Bash command run in the background
(`run_in_background: true`), so you are woken up when it exits. Substitute the installed
`sconcur/laravel` version for `CURRENT`:

```bash
current=CURRENT
while true; do
  latest=$(curl -fsS https://repo.packagist.org/p2/sconcur/laravel.json 2>/dev/null \
    | jq -r '.packages["sconcur/laravel"][].version' 2>/dev/null \
    | sed 's/^v//' | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1)
  if [ -n "$latest" ] && [ "$latest" != "$current" ] \
    && [ "$(printf '%s\n%s\n' "$current" "$latest" | sort -V | tail -1)" = "$latest" ]; then
    echo "new sconcur/laravel release: $latest"
    break
  fi
  sleep 30
done
```

Only stable `X.Y.Z` releases count. Network or parse failures just mean another round.
Do nothing else while it runs. If the background task ends without printing a new
release, start it again.

## 4. Install

- If the new version is outside the constraint in `composer.json` (a caret on `0.x` does
  not cross a minor: `^0.4` never reaches `0.5.0`), raise the constraint by hand first.
  Otherwise `make sconcur-update` reports success and silently stays on the old version.
- Run `make sconcur-update`, then `make sconcur-wait` and `make sconcur-reload`.
- Confirm in `composer.lock` that `sconcur/laravel` is on the new version, and note the
  `sconcur/sconcur` version it pulled in (old → new).
- If Packagist has the version but Composer does not see it yet (metadata cache), wait
  and retry rather than giving up.

## 5. Find out what changed

For both `sconcur/laravel` and, if it moved, `sconcur/sconcur`:

- Read the changes between the old and the new tag in the upstream repositories
  (`https://github.com/sprust/sconcur-laravel`, and the `source.url` of
  `sconcur/sconcur` in `composer.lock`): commit messages, release notes, the diff.
  Use `gh`, a clone in the scratchpad directory, or the GitHub compare view.
- Pay particular attention to renamed or removed classes and methods, changed
  signatures, new or renamed config keys and environment variables, changed artisan
  commands, service provider changes, and changes in `docs/`, `README.md` and `config/`
  of `vendor/sconcur/laravel` and `vendor/sconcur/sconcur`.
- Diff the package's `config/` against the published config in this project's `config/`.

## 6. Adapt

- Find every usage in the project affected by those changes — `app/`, `config/`,
  `bootstrap/`, `routes/`, `tests/`, `makefile`, `docker/`, `docker-compose.yml`,
  `.env.example` — and update it.
- Take up new required config and drop what the package no longer reads.
- Update project documentation that describes what changed (`README.md` and
  `README.ru.md` together, `.ai/README.md`).
- Do not adopt new optional features unless the extra instructions ask for it; list them
  in the report instead.
- Follow the conventions in `.ai/README.md`: deptrac layers, no code comments, named
  arguments on multi-line calls, and the rest.

## 7. Verify

- Run `make check` and fix what fails. If the HTTP layer or contract enums changed, run
  `make oa-generate`; if `frontend/src` changed, run `make frontend-npm-build`.
- The workers hold the code they loaded: run `make sconcur-reload` after the code
  changes, then `make sconcur-status` and confirm the servers are up and serving.
- A master restart is needed if the extension itself changed: `make sconcur-restart`,
  then `make sconcur-wait`.
- Stop any process you started only to check something.

All `make` commands are pre-authorized; do not ask before running them.

## 8. Report

Do not commit or push. Finish with:

- versions, old → new, for `sconcur/laravel` and `sconcur/sconcur`;
- what changed upstream that matters to this project;
- what you changed to adapt, file by file;
- the results of `make check` and the other commands, with failures quoted;
- new optional features worth adopting, and anything left unresolved;
- a proposed commit message following the commit rules in `.ai/README.md`
  (e.g. `chore(deps): take sconcur/laravel X.Y.Z, which ...`).
