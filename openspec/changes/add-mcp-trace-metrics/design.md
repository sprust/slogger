## Context

График страницы трейсов — `TraceTimestampsController::index` → `Trace\Domain\Actions\Queries\FindTraceTimestampsAction::handle(FindTraceTimestampsParameters)`. Действие:

1. Считает `loggedAtFrom` из пресета `TraceTimestampPeriodEnum` и `loggedAtTo` (по умолчанию `now`) и выравнивает его вниз по шагу `TraceTimestampEnum`.
2. Вызывает `TraceDynamicIndexInitializer::init(...)`. Тот собирает поля индекса из того, какие фильтры заданы (`sid`, `tss.<step>`, `lat`, `tp`, `tgs.nm`, `st`, `dur`, `mem`, `cpu`, …), и зовёт `TraceDynamicIndexRepository::findOneOrCreate`. Имя индекса — `dyn_<поля>_<почасовые коллекции периода>`, значения фильтров в него не входят. Каждое обращение продлевает `actualUntilAt` на 12 часов.
3. Бросает `TraceDynamicIndexInProcessException(indexId)`, пока индекс строится, `TraceDynamicIndexErrorException` — если построение упало, `TraceDynamicIndexNotInitException` — если записи индекса нет.
4. Параллельно читает ряд и возвращает `TraceTimestampsObjects(loggedAtFrom, items)`. `items` — `TraceTimestampsObject(timestamp, timestampTo, fields)` на каждый шаг, включая пустые. В `fields` лежат `TraceTimestampFieldObject(field, indicators)` с `TraceTimestampFieldIndicatorObject(name, value)`: для `count` — `sum`, для остальных — `avg`, `min`, `max`, `p50`, `p95`, `p99`.

Допустимые шаги пресета отдаёт `MakeTraceTimestampPeriodsAction::handle()` (через `TraceTimestampMetricsFactory::getTimestampsByDate`).

Инструменты MCP: `McpToolSchema` компилируется и в JSON Schema, и в правила валидации. Типов свойств пять (`string`, `integer`, `boolean`, `string_list`, `integer_list`), числа с дробной частью нет.

## Goals / Non-Goals

**Goals:**
- Те же числа, что на графике UI при тех же аргументах, и тот же индекс: модель и UI делят индексы.
- Модель не ждёт индекс внутри запроса и понимает, как не плодить новые.

**Non-Goals:**
- Изменение `FindTraceTimestampsAction`, инициализатора индексов и admin API.
- Фильтр и метрики по `data`, `has_profiling`, `trace_ids`.
- Бюджет индексов на подключение: переиспользование по набору полей и часам делает его ненужным при периоде не длиннее суток. Если практика покажет обратное, бюджет остаётся в `add-mcp-trace-queries`.
- `get_index_status`: повтор того же вызова даёт то же самое и дешевле объяснить.

## Decisions

### Мост вызывает действие графика как есть

`Mcp\Domain\Actions\Bridges\FindMcpTraceMetricsAction::handle(FindMcpTraceMetricsParameters): McpTraceMetricsObject`:

1. Проверяет шаг: находит пресет в `MakeTraceTimestampPeriodsAction::handle()`. Шаг не из списка — `McpTraceMetricsStepNotAllowedException(allowedSteps)`. Без шага берётся шаг по умолчанию из таблицы моста (см. спеку).
2. Строит `FindTraceTimestampsParameters`, где `dataFields`, `traceIds`, `data`, `hasProfiling` равны `null`, и вызывает `FindTraceTimestampsAction::handle`.
3. Переводит исключения `Trace` в исключения `Mcp`:
   - `TraceDynamicIndexInProcessException` → `McpTraceIndexBuildingException(indexId)`;
   - `TraceDynamicIndexErrorException` и `TraceDynamicIndexNotInitException` → `McpTraceIndexFailedException(message)`;
   - `TraceDynamicIndexParallelArraysException` не возникает: фильтра по `data` нет.

Результат `Trace` отдаётся как есть, по правилу мостов модуля, вместе с шагом, который выбрал мост: `McpTraceMetricsObject(step, timestamps)` в `Mcp\Entities\Bridges`. Шаг нужен ответу инструмента, а выбирает его только мост. Инструмент не знает об исключениях `Trace`, поэтому ребро из `Mcp\Infrastructure` в `Trace\Domain` не появляется.

Альтернатива — вызвать `TraceDynamicIndexInitializer` и репозиторий напрямую, чтобы свернуть ответ под MCP. Отклонена: расчёт графика разошёлся бы с UI, а общие индексы перестали бы быть общими.

### Шаги по умолчанию

Самый мелкий шаг пресета, который даёт не больше 60 точек: `5 minutes` — `s5`, `30 minutes` — `s30`, `1 hour` — `min`, `4 hours` — `min5`, `12 hours` — `min30`, `1 day` — `min30`. Таблица фиксированная: шаг входит в имя индекса (`tss.<step>`), и разные умолчания для одного пресета строили бы разные индексы.

### Период — только пресеты до суток

`period` в схеме инструмента — перечисление из шести пресетов, поэтому длинный пресет отсекает валидатор схемы (`-32602`). Сутки — до 25 почасовых коллекций на индекс. Это тот же порядок, что в `add-mcp-trace-queries`. `to` не выравнивается: коллекции и так почасовые, а скользящее `to` меняет имя индекса только на границе часа.

### Сервисы и время проверяет инструмент

`GetTraceMetricsTool`:
- разбирает `to` (ISO 8601, `invalid_time`);
- проверяет `service_ids` через существующий мост `FindMcpServicesAction` (`service_not_found`);
- строит `FindMcpTraceMetricsParameters`;
- переводит исключения моста в `index_building` (статус, не ошибка), `index_error`, `step_not_allowed`.

Ответ собирается из `McpTraceMetricsObject`: `step` — шаг моста, `from` = `timestamps->loggedAtFrom`, шаги = `timestamps->items`. Поля графика названы ключами хранилища (`count`, `dur`, `mem`, `cpu`), инструмент сопоставляет их с метриками. Метрика, которой нет в шаге, отдаётся нулём для `count` и `null` для индикаторов. Для `count` берётся индикатор `sum` как целое, для остальных метрик — объект шести индикаторов. Время — через `McpToolFormatter::time`.

### Числовой тип свойства

В `McpToolPropertyTypeEnum` добавляется `Number` (`number` в JSON Schema, правило `numeric`), в `McpToolArguments` — `floatNull`. Это нужно для `duration_from`/`to`, `memory_from`/`to`, `cpu_from`/`to`.

### Инструкции сервера

В `resources/mcp/instructions.md`:
- новый шаг метода: `get_trace_metrics` — сколько трейсов, сколько упало, как менялась длительность;
- правило: для сравнения меняй значения фильтров, но не их набор, шаг и период;
- на `index_building` сделай что-то полезное и повтори тот же вызов, как с `tree_building`.

## Risks / Trade-offs

- [Модель перебирает наборы фильтров и шаги и строит много индексов] → период не длиннее суток, описание и инструкции объясняют переиспользование. Индексы видны и удаляются в UI на странице динамических индексов. Бюджет можно добавить позже в `add-mcp-trace-queries`.
- [Первый вызов почти всегда `index_building`, а модель сдаётся] → `hint` и инструкции явно просят повторить тот же вызов, как у `tree_building`.
- [Сутки на шаге `min30` по всем сервисам — тяжёлый индекс на большой инсталляции] → тот же индекс строит UI при том же графике; нагрузка того же порядка, что у пользователя.
- [Перцентили у пустых шагов] → в пустом шаге индикаторы берутся из `emptyIndicators` действия, как в UI; спека требует только `count: 0`.
- [Назначение спеки `mcp-tools` говорит «без динамических индексов»] → при архивации назначение правится вручную (задача в `tasks.md`).

## Migration Plan

Миграций нет. Откат — убрать инструмент из `McpServiceProvider::TOOLS` и шаг из инструкций. Индексы, построенные инструментом, удаляются по `actualUntilAt`, как индексы UI.
