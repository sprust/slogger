# mcp-trace-queries Specification

## Purpose
Инструменты MCP, которые выбирают трейсы сервиса за период и показывают динамические индексы трейсов: правило периода, проверка сервиса, фильтр по `data`, статус построения индекса и ответы инструментов в компактном формате для модели.

## Requirements

### Requirement: Правило периода

Инструменты `get_trace_facets`, `search_traces`, `get_trace_data_fields`, `aggregate_traces` и `compare_trace_groups` SHALL требовать аргументы `from` и `to` и принимать необязательный `service_ids` — до 20 id сервисов; без него выборка идёт по всем сервисам. `from` и `to` — время в ISO 8601; другое значение SHALL давать ошибку инструмента `invalid_time` с именем аргумента. Период SHALL выравниваться по часам UTC: `from` вниз до начала часа, `to` вверх до начала следующего часа (время ровно на границе часа не сдвигается); если границы совпали, период — этот час. `from` позже `to` SHALL давать ошибку инструмента `invalid_period`. Выровненный период длиннее 24 часов SHALL давать ошибку инструмента `period_too_wide` с подсказкой сузить период, например по группам `aggregate_traces` по часам. Неизвестный id в `service_ids` SHALL давать ошибку инструмента `service_not_found` со списком неизвестных id. Ответ SHALL содержать выровненные `from` и `to`.

#### Scenario: Выравнивание
- **WHEN** модель вызывает `search_traces` с `from: "2026-09-28T10:20:00Z"` и `to: "2026-09-28T12:05:00Z"`
- **THEN** выборка идёт за период с 10:00 до 13:00, ответ содержит `from` `2026-09-28T10:00:00Z` и `to` `2026-09-28T13:00:00Z`

#### Scenario: Слишком длинный период
- **WHEN** модель вызывает `get_trace_facets` с периодом 30 часов
- **THEN** ответ — ошибка инструмента `period_too_wide`, выборка не выполняется

#### Scenario: Начало позже конца
- **WHEN** модель вызывает `search_traces` с `from` позже `to`
- **THEN** ответ — ошибка инструмента `invalid_period`

#### Scenario: Неверное время
- **WHEN** модель вызывает `search_traces` с `from: "yesterday"`
- **THEN** ответ — ошибка инструмента `invalid_time`, в тексте которой названо `from`

#### Scenario: Неизвестный сервис
- **WHEN** модель вызывает `get_trace_facets` с `service_ids: [2, 999]`, а сервиса `999` нет
- **THEN** ответ — ошибка инструмента `service_not_found`, в тексте которой есть `999`

#### Scenario: Все сервисы
- **WHEN** модель вызывает `search_traces` без `service_ids`
- **THEN** выборка идёт по трейсам всех сервисов

### Requirement: Статус построения индекса

Инструмент, запрос которого требует динамического индекса, который ещё строится, SHALL отвечать не ошибкой, а статусом `{"status": "index_building", "index_id": ..., "retry_after_seconds": 10, "hint": ...}`. `hint` SHALL просить повторить тот же вызов позже или следить за индексом через `get_trace_index_status` и предупреждать, что другой набор фильтров или другие часы начнут строить другой индекс. Если построение индекса завершилось ошибкой, ответ SHALL быть ошибкой инструмента `index_error` с текстом ошибки. Так SHALL отвечать `get_trace_facets`, `search_traces`, `get_trace_data_fields`, `aggregate_traces` и `compare_trace_groups`.

#### Scenario: Индекс строится
- **WHEN** индекса для набора фильтров и часов ещё нет, и модель вызывает `search_traces`
- **THEN** построение запускается, ответ — статус `index_building` с `index_id` без `isError`

#### Scenario: Индекс упал
- **WHEN** построение индекса завершилось ошибкой, и модель вызывает `search_traces` с теми же аргументами
- **THEN** ответ — ошибка инструмента `index_error` с текстом ошибки индекса

### Requirement: get_trace_facets

Инструмент `get_trace_facets` с аргументами правила периода и необязательными `types` и `statuses` (до 20 значений) SHALL возвращать три списка `{value, count}` за период по выбранным сервисам: `types`, `statuses`, `tags`, каждый отсортирован по `count` по убыванию. Список типов SHALL считаться без фильтра `types`, список статусов — без фильтра `statuses`, как фильтры страницы трейсов в UI. В каждом списке SHALL быть не больше `MCP_FACETS_LIMIT` (по умолчанию 50) значений; ответ SHALL содержать `truncated` по каждому списку. Пока строится хотя бы один из индексов, ответ SHALL быть статусом `index_building`.

#### Scenario: Обзор сервиса за час
- **WHEN** индексы готовы, и модель вызывает `get_trace_facets` для сервиса за час
- **THEN** ответ содержит `types`, `statuses` и `tags` со значениями и количеством, отсортированные по убыванию количества

#### Scenario: Фильтр по статусу
- **WHEN** модель вызывает `get_trace_facets` с `statuses: ["failed"]`
- **THEN** `types` и `tags` посчитаны только по упавшим трейсам, а `statuses` — по всем

### Requirement: search_traces

Инструмент `search_traces` с аргументами правила периода и необязательными `types`, `statuses`, `tags` (до 20 значений), `duration_from`, `duration_to`, `data_filter`, `data_fields` и `page` SHALL возвращать страницу трейсов за период, новые первыми, до 20 трейсов. На трейс: `trace_id`, `parent_trace_id`, `type`, `status`, `tags`, `duration`, `memory`, `cpu`, `logged_at` и `data` — значения запрошенных `data_fields` по их ключам (ключа нет в `data`, если в трейсе нет поля). Ответ SHALL содержать `page` и `has_more` (страница полная).

`data_filter` — до 3 условий строкой `<ключ> <оператор> <значение>`, где ключ — путь в `data` через точку:
- `= != > >= < <=` с числом — числовое сравнение;
- `= !=` с `true` или `false` — логическое сравнение (`!=` инвертирует значение);
- `= != contains starts ends` со строкой в двойных кавычках — строковое сравнение;
- `exists`, `missing`, `is null`, `is not null` без значения.

Условие не по этому формату SHALL давать ошибку инструмента `invalid_data_filter` с условием и форматом. `tags` вместе с `data_filter` SHALL давать ошибку инструмента `tags_with_data_filter` до выборки. `data_fields` — до 10 ключей.

#### Scenario: Упавшие трейсы
- **WHEN** индекс готов, и модель вызывает `search_traces` с `statuses: ["failed"]`
- **THEN** ответ содержит до 20 упавших трейсов сервиса за период, новые первыми, без `has_profiling`

#### Scenario: Фильтр и поля data
- **WHEN** модель вызывает `search_traces` с `data_filter: ["response.status >= 500"]` и `data_fields: ["request.uri"]`
- **THEN** в ответе только трейсы, у которых `response.status` не меньше 500, у каждого `data` содержит `request.uri`

#### Scenario: Следующая страница
- **WHEN** ответ содержит `has_more: true`, и модель вызывает `search_traces` с теми же аргументами и `page: 2`
- **THEN** ответ содержит следующие трейсы

#### Scenario: Неверное условие
- **WHEN** модель вызывает `search_traces` с `data_filter: ["status ~ 5"]`
- **THEN** ответ — ошибка инструмента `invalid_data_filter`

#### Scenario: Теги и фильтр по data
- **WHEN** модель вызывает `search_traces` с `tags` и `data_filter`
- **THEN** ответ — ошибка инструмента `tags_with_data_filter`, индекс не строится

### Requirement: get_trace_data_fields

Инструмент `get_trace_data_fields` с аргументами правила периода и `type` SHALL брать до 5 последних трейсов этого типа за период и возвращать ключи их `data` — пути через точку до значений — с примером значения, в порядке первого появления, не больше 200 ключей, и `traces_count` — сколько трейсов просмотрено. Если трейсов типа за период нет, список SHALL быть пустым.

#### Scenario: Ключи data
- **WHEN** у трейсов типа `request` в `data` есть `request.uri` и `response.status`, и модель вызывает `get_trace_data_fields`
- **THEN** ответ содержит `request.uri` и `response.status` с примерами значений

#### Scenario: Нет трейсов
- **WHEN** трейсов типа за период нет
- **THEN** ответ содержит пустой `fields` и `traces_count: 0`

### Requirement: get_trace_index_status

Инструмент `get_trace_index_status` с аргументом `index_id` SHALL возвращать статус динамического индекса: `building` с `progress` (от 0 до 1 по коллекциям, где индекс строится сейчас, или `null`, если построение ещё не началось) и `retry_after_seconds`, `ready`, `error` с текстом ошибки или `not_found`, если индекс удалён или истёк. Инструмент SHALL NOT строить индексы.

#### Scenario: Индекс строится
- **WHEN** модель вызывает `get_trace_index_status` с `index_id` из статуса `index_building`
- **THEN** ответ — `building` с `progress` и `retry_after_seconds`

#### Scenario: Индекс готов
- **WHEN** индекс построен, и модель вызывает `get_trace_index_status`
- **THEN** ответ — `ready`

#### Scenario: Индекса нет
- **WHEN** модель вызывает `get_trace_index_status` с `index_id`, которого нет
- **THEN** ответ — `not_found` без `isError`

### Requirement: get_trace_indexes

Инструмент `get_trace_indexes` без аргументов SHALL возвращать существующие динамические индексы, новые первыми, не больше 100: `index_id`, поля индекса (имя поля и его название, как в UI), первый и последний час коллекций, статус (`building`, `ready`, `error`) и `actual_until_at`. Инструмент SHALL NOT строить индексы.

#### Scenario: Готовые индексы
- **WHEN** есть индексы, построенные UI или моделью, и модель вызывает `get_trace_indexes`
- **THEN** ответ содержит их поля, часы и статусы, новые первыми

### Requirement: aggregate_traces

Инструмент `aggregate_traces` с аргументами правила периода, обязательным `by` — от одного до трёх полей из `service`, `type`, `status`, `hour`, `minute10` без повторов (не больше одного времени) — и необязательными фильтрами `search_traces` без `data_filter` (`types`, `statuses`, `tags`, `duration_from`, `duration_to`) SHALL группировать трейсы периода по полям `by` одной агрегацией и возвращать группы: значения полей группы (`service` как `{id, name}`, `type`, `status`, `hour` или `minute10` — начало интервала), `count`, `duration_avg`, `duration_p95`, `duration_max` и `example_trace_id` — самый долгий трейс группы. Группы SHALL быть отсортированы по времени по возрастанию, если время есть в `by`, затем по `count` по убыванию; их SHALL быть не больше 100, ответ SHALL содержать `truncated`. Индекс SHALL строиться по тому же набору полей, что у `search_traces` с теми же фильтрами.

#### Scenario: Типы по сервисам
- **WHEN** индекс готов, и модель вызывает `aggregate_traces` с `by: ["service", "type"]` за час
- **THEN** ответ содержит группы «сервис × тип» с количеством и длительностями, самые частые первыми

#### Scenario: Окно по времени
- **WHEN** модель вызывает `aggregate_traces` с `by: ["minute10"]` и `statuses: ["failed"]`
- **THEN** ответ содержит 10-минутные интервалы по возрастанию времени с числом упавших трейсов и p95 длительности

#### Scenario: Неверные поля
- **WHEN** модель вызывает `aggregate_traces` с `by: ["hour", "minute10"]`
- **THEN** ответ — ошибка инструмента `invalid_group_by`

### Requirement: compare_trace_groups

Инструмент `compare_trace_groups` с аргументами правила периода, необязательным `types`, обязательными `group_a_statuses` (до 20), необязательным `group_b_statuses` (до 20; без него группа B — трейсы с любыми другими статусами) и обязательным `by` — `type`, `service`, `tag` или `data.<ключ>` — SHALL распределять трейсы обеих групп по значениям `by` одной агрегацией и возвращать `group_a_total`, `group_b_total` и строки: `value` (для `service` — `{id, name}`), `group_a_count`, `group_a_share`, `group_b_count`, `group_b_share` (доли от 0 до 1, округлённые до четырёх знаков). Строки SHALL быть отсортированы по `group_a_share − group_b_share` по убыванию; их SHALL быть не больше 30, ответ SHALL содержать `truncated`. Трейс без значения `by` (без тегов, без ключа `data`) SHALL попадать в строку с `value: null`. Статус из `group_b_statuses`, который есть и в `group_a_statuses`, SHALL давать ошибку инструмента `overlapping_groups`; `by` не по формату — `invalid_group_by`.

#### Scenario: Чем упавшие отличаются
- **WHEN** модель вызывает `compare_trace_groups` с `group_a_statuses: ["failed"]` и `by: "type"`
- **THEN** первой строкой идёт тип, доля которого среди упавших больше всего превышает его долю среди остальных

#### Scenario: По ключу data
- **WHEN** модель вызывает `compare_trace_groups` с `by: "data.response.status"`
- **THEN** строки содержат значения `response.status` с количеством и долями в обеих группах

#### Scenario: Пересекающиеся группы
- **WHEN** модель вызывает `compare_trace_groups` с `group_a_statuses: ["failed"]` и `group_b_statuses: ["failed", "success"]`
- **THEN** ответ — ошибка инструмента `overlapping_groups`
