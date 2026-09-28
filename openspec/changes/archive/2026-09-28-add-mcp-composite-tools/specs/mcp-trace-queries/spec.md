## MODIFIED Requirements

### Requirement: Правило периода

Инструменты `trace_facets`, `find_traces`, `list_trace_data_fields`, `top_trace_groups` и `compare_trace_groups` SHALL требовать аргументы `from` и `to` и принимать необязательный `service_ids` — до 20 id сервисов; без него выборка идёт по всем сервисам. `from` и `to` — время в ISO 8601; другое значение SHALL давать ошибку инструмента `invalid_time` с именем аргумента. Период SHALL выравниваться по часам UTC: `from` вниз до начала часа, `to` вверх до начала следующего часа (время ровно на границе часа не сдвигается); если границы совпали, период — этот час. `from` позже `to` SHALL давать ошибку инструмента `invalid_period`. Выровненный период длиннее 24 часов SHALL давать ошибку инструмента `period_too_wide` с подсказкой сузить период, например по группам `top_trace_groups` по часам. Неизвестный id в `service_ids` SHALL давать ошибку инструмента `service_not_found` со списком неизвестных id. Ответ SHALL содержать выровненные `from` и `to`.

#### Scenario: Выравнивание
- **WHEN** модель вызывает `find_traces` с `from: "2026-09-28T10:20:00Z"` и `to: "2026-09-28T12:05:00Z"`
- **THEN** выборка идёт за период с 10:00 до 13:00, ответ содержит `from` `2026-09-28T10:00:00Z` и `to` `2026-09-28T13:00:00Z`

#### Scenario: Слишком длинный период
- **WHEN** модель вызывает `trace_facets` с периодом 30 часов
- **THEN** ответ — ошибка инструмента `period_too_wide`, выборка не выполняется

#### Scenario: Начало позже конца
- **WHEN** модель вызывает `find_traces` с `from` позже `to`
- **THEN** ответ — ошибка инструмента `invalid_period`

#### Scenario: Неверное время
- **WHEN** модель вызывает `find_traces` с `from: "yesterday"`
- **THEN** ответ — ошибка инструмента `invalid_time`, в тексте которой названо `from`

#### Scenario: Неизвестный сервис
- **WHEN** модель вызывает `trace_facets` с `service_ids: [2, 999]`, а сервиса `999` нет
- **THEN** ответ — ошибка инструмента `service_not_found`, в тексте которой есть `999`

#### Scenario: Все сервисы
- **WHEN** модель вызывает `find_traces` без `service_ids`
- **THEN** выборка идёт по трейсам всех сервисов

### Requirement: Статус построения индекса

Инструмент, запрос которого требует динамического индекса, который ещё строится, SHALL отвечать не ошибкой, а статусом `{"status": "index_building", "index_id": ..., "retry_after_seconds": 10, "hint": ...}`. `hint` SHALL просить повторить тот же вызов позже или следить за индексом через `get_index_status` и предупреждать, что другой набор фильтров или другие часы начнут строить другой индекс. Если построение индекса завершилось ошибкой, ответ SHALL быть ошибкой инструмента `index_error` с текстом ошибки. Так SHALL отвечать `trace_facets`, `find_traces`, `list_trace_data_fields`, `top_trace_groups` и `compare_trace_groups`.

#### Scenario: Индекс строится
- **WHEN** индекса для набора фильтров и часов ещё нет, и модель вызывает `find_traces`
- **THEN** построение запускается, ответ — статус `index_building` с `index_id` без `isError`

#### Scenario: Индекс упал
- **WHEN** построение индекса завершилось ошибкой, и модель вызывает `find_traces` с теми же аргументами
- **THEN** ответ — ошибка инструмента `index_error` с текстом ошибки индекса

## ADDED Requirements

### Requirement: top_trace_groups

Инструмент `top_trace_groups` с аргументами правила периода, обязательным `by` — от одного до трёх полей из `service`, `type`, `status`, `hour`, `minute10` без повторов (не больше одного времени) — и необязательными фильтрами `find_traces` без `data_filter` (`types`, `statuses`, `tags`, `duration_from`, `duration_to`) SHALL группировать трейсы периода по полям `by` одной агрегацией и возвращать группы: значения полей группы (`service` как `{id, name}`, `type`, `status`, `hour` или `minute10` — начало интервала), `count`, `duration_avg`, `duration_p95`, `duration_max` и `example_trace_id` — самый долгий трейс группы. Группы SHALL быть отсортированы по времени по возрастанию, если время есть в `by`, затем по `count` по убыванию; их SHALL быть не больше 100, ответ SHALL содержать `truncated`. Индекс SHALL строиться по тому же набору полей, что у `find_traces` с теми же фильтрами.

#### Scenario: Типы по сервисам
- **WHEN** индекс готов, и модель вызывает `top_trace_groups` с `by: ["service", "type"]` за час
- **THEN** ответ содержит группы «сервис × тип» с количеством и длительностями, самые частые первыми

#### Scenario: Окно по времени
- **WHEN** модель вызывает `top_trace_groups` с `by: ["minute10"]` и `statuses: ["failed"]`
- **THEN** ответ содержит 10-минутные интервалы по возрастанию времени с числом упавших трейсов и p95 длительности

#### Scenario: Неверные поля
- **WHEN** модель вызывает `top_trace_groups` с `by: ["hour", "minute10"]`
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
