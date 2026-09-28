# mcp-tools Specification

## Purpose
Инструменты MCP, которые читают сервисы, инциденты смотрителей и отдельные трейсы без динамических индексов трейсов, в компактном формате для модели.

## Requirements

### Requirement: Общий формат ответа

Каждый ответ инструмента SHALL быть объектом JSON и содержать поле `installation` со значением `server_name` инсталляции. Время SHALL отдаваться в ISO 8601 UTC. Строковые значения длиннее `MCP_MAX_STRING_LENGTH` (по умолчанию 500) SHALL обрезаться с явной пометкой об обрезке; исключение — `get_trace_data`. Ни один инструмент SHALL NOT изменять данные; единственный допустимый побочный эффект — построение кэша дерева трейса, как при открытии дерева в UI.

#### Scenario: Поле installation
- **WHEN** модель вызывает любой инструмент на инсталляции `stand`
- **THEN** ответ содержит `"installation": "stand"`

#### Scenario: Длинная строка
- **WHEN** тег трейса длиннее 500 символов, а модель вызывает `get_trace`
- **THEN** в ответе тег обрезан до 500 символов и помечен как обрезанный

### Requirement: list_services

Инструмент `list_services` с необязательным аргументом `query` SHALL возвращать сервисы инсталляции (`id`, `name`). С `query` SHALL возвращаться только сервисы, имя которых содержит `query` без учёта регистра.

#### Scenario: Все сервисы
- **WHEN** модель вызывает `list_services` без аргументов
- **THEN** ответ содержит все сервисы с `id` и `name`

#### Scenario: Поиск
- **WHEN** есть сервисы `billing` и `auth`, а модель вызывает `list_services` с `query: "BILL"`
- **THEN** ответ содержит только `billing`

### Requirement: get_data_range

Инструмент `get_data_range` без аргументов SHALL возвращать первый и последний час, за которые хранятся трейсы (`first_hour`, `last_hour`). Если трейсов нет, оба значения SHALL быть `null`.

#### Scenario: Есть трейсы
- **WHEN** трейсы хранятся за часы с 2026-09-25 10:00 по 2026-09-28 14:00 UTC
- **THEN** `first_hour` равно `2026-09-25T10:00:00Z`, `last_hour` равно `2026-09-28T14:00:00Z`

#### Scenario: Нет трейсов
- **WHEN** трейсов нет
- **THEN** `first_hour` и `last_hour` равны `null`

### Requirement: list_incidents

Инструмент `list_incidents` с необязательными аргументами `status` (`opened` или `closed`), `watcher_id` и `page` SHALL возвращать инциденты смотрителей в порядке, в котором их показывает UI: открытые первыми, затем новые первыми. На инцидент: `id`, `status`, `first_event_at`, `last_event_at`, `events_count`, `closed_at` и смотритель — `id`, `name`, `type` и `service_ids` из его фильтра по трейсам (пустой список, если фильтр по сервисам не задан). Страница — до 50 инцидентов, ответ содержит `has_more`.

#### Scenario: Открытые инциденты
- **WHEN** модель вызывает `list_incidents` с `status: "opened"`
- **THEN** ответ содержит только открытые инциденты, у каждого есть смотритель с `name`, `type` и `service_ids`

#### Scenario: Несколько страниц
- **WHEN** инцидентов 70, и модель вызывает `list_incidents` без `page`
- **THEN** ответ содержит 50 инцидентов и `has_more: true`

### Requirement: get_incident_events

Инструмент `get_incident_events` с аргументами `incident_id` и необязательным `page` SHALL возвращать инцидент (как в `list_incidents`) и его события: `occurred_at` и числа события под теми именами, под которыми они хранятся для типа смотрителя. Страница — до 50 событий, ответ содержит `has_more`. Неизвестный `incident_id` SHALL давать ошибку инструмента.

#### Scenario: События инцидента
- **WHEN** модель вызывает `get_incident_events` с `id` существующего инцидента
- **THEN** ответ содержит инцидент и события с `occurred_at` и числами события

#### Scenario: Нет инцидента
- **WHEN** модель вызывает `get_incident_events` с несуществующим `incident_id`
- **THEN** ответ — ошибка инструмента (`isError: true`) с текстом, что инцидент не найден

### Requirement: get_trace

Инструмент `get_trace` с аргументом `trace_id` SHALL возвращать трейс без `data`: сервис (`id`, `name`), `trace_id`, `parent_trace_id`, `type`, `status`, `tags`, `duration`, `memory`, `cpu`, `logged_at`. Неизвестный `trace_id` SHALL давать ошибку инструмента.

#### Scenario: Трейс найден
- **WHEN** модель вызывает `get_trace` с существующим `trace_id`
- **THEN** ответ содержит перечисленные поля и не содержит `data`

#### Scenario: Трейс не найден
- **WHEN** модель вызывает `get_trace` с несуществующим `trace_id`
- **THEN** ответ — ошибка инструмента с текстом, что трейс не найден

### Requirement: get_trace_data

Инструмент `get_trace_data` с аргументом `trace_id` SHALL возвращать `data` трейса в том же виде, в каком его получает UI в детали трейса: без маскирования и без обрезки строк. Неизвестный `trace_id` SHALL давать ошибку инструмента.

#### Scenario: Данные трейса
- **WHEN** модель вызывает `get_trace_data` с существующим `trace_id`
- **THEN** поле `data` ответа совпадает с `data` детали трейса в admin API

### Requirement: get_trace_tree

Инструмент `get_trace_tree` с аргументами `trace_id` и необязательными `parent_trace_id` и `cursor` SHALL возвращать дерево, в которое входит трейс `trace_id`, ветками: без `parent_trace_id` — верхний уровень от корня, с `parent_trace_id` — детей этого узла. Ответ готового дерева содержит `status: "ready"`, `root_trace_id` и `next_cursor`. На узел: `trace_id`, `parent_trace_id`, `service`, `type`, `status`, `duration`, число детей (`children_count`). За вызов отдаётся не больше `MCP_TREE_NODES_LIMIT` узлов (по умолчанию 300); если узлов больше, ответ содержит `next_cursor` для следующего вызова. Инструмент SHALL NOT перестраивать уже построенное дерево.

Если кэш дерева ещё не построен, первый вызов SHALL запустить его построение, как первое открытие дерева в UI. Пока дерево строится, ответ SHALL быть не ошибкой, а статусом `{"status": "tree_building", "retry_after_seconds": 10, "hint": ...}`, где `hint` просит повторить тот же вызов позже. Если построение завершилось ошибкой или отменено, ответ SHALL быть ошибкой инструмента с текстом, что перестроить дерево можно только в UI.

#### Scenario: Первое открытие
- **WHEN** модель вызывает `get_trace_tree` для трейса, дерево которого ещё не строилось
- **THEN** построение запускается, ответ — статус `tree_building` без `isError`

#### Scenario: Дерево готово
- **WHEN** дерево построено, и модель вызывает `get_trace_tree` без `parent_trace_id`
- **THEN** ответ содержит узлы верхнего уровня с числом детей у каждого

#### Scenario: Большая ветка
- **WHEN** у узла 1000 детей, и модель вызывает `get_trace_tree` с его `parent_trace_id`
- **THEN** ответ содержит 300 узлов и `next_cursor`; вызов с этим `cursor` отдаёт следующие узлы

#### Scenario: Построение упало
- **WHEN** построение дерева завершилось ошибкой, и модель вызывает `get_trace_tree`
- **THEN** ответ — ошибка инструмента с текстом, что перестроить дерево можно только в UI, а новое построение не запускается

#### Scenario: Трейс не найден
- **WHEN** модель вызывает `get_trace_tree` с несуществующим `trace_id`
- **THEN** ответ — ошибка инструмента с текстом, что трейс не найден

### Requirement: find_in_trace_tree

Инструмент `find_in_trace_tree` с аргументами `trace_id` и хотя бы одним из фильтров `service_ids`, `types`, `tags`, `statuses` SHALL возвращать только узлы дерева, подходящие под фильтр (в формате узлов `get_trace_tree`, но без `children_count`), не больше `MCP_TREE_NODES_LIMIT`, их общее число `matched_count` и признак `truncated`, если совпадений больше, чем отдано. Пока дерево не построено, ответ SHALL быть тем же статусом `tree_building`, что у `get_trace_tree`, и SHALL NOT запускать построение сам.

#### Scenario: Поиск упавших узлов
- **WHEN** дерево построено, и модель вызывает `find_in_trace_tree` с `statuses: ["failed"]`
- **THEN** ответ содержит только узлы со статусом `failed`, `matched_count` и `truncated`

#### Scenario: Без фильтров
- **WHEN** модель вызывает `find_in_trace_tree` только с `trace_id`
- **THEN** ответ — ошибка JSON-RPC `-32602`

#### Scenario: Дерево не построено
- **WHEN** дерево трейса не строилось, и модель вызывает `find_in_trace_tree`
- **THEN** ответ — статус `tree_building` с подсказкой сначала вызвать `get_trace_tree`
