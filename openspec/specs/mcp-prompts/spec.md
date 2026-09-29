# mcp-prompts Specification

## Purpose
Промпты сервера MCP: готовые сценарии расследования, которые клиент показывает пользователю как команды, с аргументами и порядком шагов, согласованным с инструкциями сервера.

## Requirements

### Requirement: Список промптов

`prompts/list` SHALL возвращать четыре промпта в неизменном порядке: `investigate_errors`, `investigate_latency`, `explain_incident`, `explain_trace`. У каждого `name`, `title`, `description` и `arguments` с `name`, `description`, `required`.

Аргументы:
- `investigate_errors`: `service` (обязательный) — имя или id сервиса, `period` (обязательный) — период словами или временем, например `last 3 hours`;
- `investigate_latency`: `service` и `period` (обязательные), `type` (необязательный) — тип трейсов;
- `explain_incident`: `incident_id` (обязательный);
- `explain_trace`: `trace_id` (обязательный).

#### Scenario: Список
- **WHEN** клиент вызывает `prompts/list`
- **THEN** ответ содержит четыре промпта с аргументами, у `investigate_latency` аргумент `type` необязательный

### Requirement: Содержание промпта

`prompts/get` SHALL возвращать одно сообщение пользователя на английском с подставленными аргументами и порядком шагов, в котором называются инструменты сервера:
- `investigate_errors` — сервис через `get_services`, окно ошибок через `aggregate_traces` со `statuses: ["failed"]` по `hour` или `minute10`, инциденты, `aggregate_traces` по `type` и `compare_trace_groups` в найденном окне, `search_traces` по упавшим трейсам, разбор нескольких трейсов через `get_trace` и `get_trace_tree`;
- `investigate_latency` — `aggregate_traces` по времени с `duration_p95`, окно роста, `aggregate_traces` по `type`, `search_traces` с `duration_from`, медленные вызовы через `search_trace_tree`;
- `explain_incident` — `get_incident_events`, окно инцидента в `aggregate_traces` по `minute10` и `search_traces`;
- `explain_trace` — `get_trace`, `get_trace_tree`, `search_trace_tree`, `get_trace_data` для узлов, которые объясняют результат.

Необязательный аргумент, которого нет, SHALL не попадать в текст. Отсутствие обязательного аргумента SHALL давать ошибку JSON-RPC `-32602`.

#### Scenario: Подстановка аргументов
- **WHEN** клиент вызывает `prompts/get` для `investigate_errors` с `service: "pms"` и `period: "last 3 hours"`
- **THEN** сообщение содержит `pms`, `last 3 hours` и шаги с `aggregate_traces` и `search_traces`

#### Scenario: Без необязательного аргумента
- **WHEN** клиент вызывает `prompts/get` для `investigate_latency` без `type`
- **THEN** сообщение не упоминает тип трейсов

#### Scenario: Нет обязательного аргумента
- **WHEN** клиент вызывает `prompts/get` для `explain_trace` без `trace_id`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`
