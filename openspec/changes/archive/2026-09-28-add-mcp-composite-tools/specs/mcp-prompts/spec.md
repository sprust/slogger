## MODIFIED Requirements

### Requirement: Содержание промпта

`prompts/get` SHALL возвращать одно сообщение пользователя на английском с подставленными аргументами и порядком шагов, в котором называются инструменты сервера:
- `investigate_errors` — сервис через `list_services`, окно ошибок через `top_trace_groups` со `statuses: ["failed"]` по `hour` или `minute10`, инциденты, `top_trace_groups` по `type` и `compare_trace_groups` в найденном окне, `find_traces` по упавшим трейсам, разбор нескольких трейсов через `get_trace` и `get_trace_tree`;
- `investigate_latency` — `top_trace_groups` по времени с `duration_p95`, окно роста, `top_trace_groups` по `type`, `find_traces` с `duration_from`, медленные вызовы через `find_in_trace_tree`;
- `explain_incident` — `get_incident_events`, окно инцидента в `top_trace_groups` по `minute10` и `find_traces`;
- `explain_trace` — `get_trace`, `get_trace_tree`, `find_in_trace_tree`, `get_trace_data` для узлов, которые объясняют результат.

Необязательный аргумент, которого нет, SHALL не попадать в текст. Отсутствие обязательного аргумента SHALL давать ошибку JSON-RPC `-32602`.

#### Scenario: Подстановка аргументов
- **WHEN** клиент вызывает `prompts/get` для `investigate_errors` с `service: "pms"` и `period: "last 3 hours"`
- **THEN** сообщение содержит `pms`, `last 3 hours` и шаги с `top_trace_groups` и `find_traces`

#### Scenario: Без необязательного аргумента
- **WHEN** клиент вызывает `prompts/get` для `investigate_latency` без `type`
- **THEN** сообщение не упоминает тип трейсов

#### Scenario: Нет обязательного аргумента
- **WHEN** клиент вызывает `prompts/get` для `explain_trace` без `trace_id`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`
