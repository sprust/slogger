## Context

- Трейсы лежат в почасовых коллекциях; выборки `find_traces` и фасетов вызывают `TraceDynamicIndexInitializer::init` с полями фильтра (`sid`, `lat`, `tp`, `st`, `tgs.nm`, `dur`), затем читают коллекции периода. `TraceTimestampsRepository` после `ad37170b` уже умеет объединять коллекции `$unionWith` и группировать один раз.
- `TracePipelineBuilder::make(...)` собирает `$match` по фильтрам; поле тегов — `tgs` (массив объектов `{nm}`), данные — `dt`, длительность — `dur`, id — `tid`, время — `lat`.
- MongoDB 8.0: есть `$dateTrunc` (с `binSize`), `$percentile`, `$top`.
- Модуль Logs: `FindLogFilesAction::handle(): LogFileObject[]` (с `source` и `type`), `FindLogEntriesAction::handle(FindLogEntriesParameters): LogEntriesPageObject` (`items` — `LogEntryViewObject`, `indexing`, `total`), время в секундах, уровни — ключи `<type>.<NAME>` (`LogLevelKeys::makeKey`), имена уровней формата — `LogFormatRegistry::get($type)->getLevelNames()`.
- `get_trace_metrics` — `GetTraceMetricsTool`, `FindMcpTraceMetricsAction`, `FindMcpTraceMetricsParameters`, `McpTraceMetricsObject`, `McpTraceMetricsStepNotAllowedException`, тесты; `McpToolTimeParser`, `McpToolServiceFinder`, `McpTraceIndexExceptionTranslator` и тип свойства `Number` используют и другие инструменты.

## Goals / Non-Goals

**Goals:**
- Обзорный вопрос — один вызов и один индекс, общий с `find_traces`.
- Меньше инструментов с разными правилами: один способ задать период.

**Non-Goals:**
- Изменение графика страницы трейсов и admin API.
- `data_filter` в группировке и сравнении: набор полей индекса растёт с каждым ключом `data`, а для обзора хватает фильтров по типам и статусам.
- Разбивка по тегам в `top_trace_groups`: тег — массив, группа по нему считает трейс несколько раз; для тегов есть `compare_trace_groups` и `trace_facets`.

## Decisions

### Агрегации в Trace

`Trace\Repositories\TraceGroupsRepository` — два метода, оба строят `$match` через `TracePipelineBuilder`, объединяют коллекции периода (`PeriodicTraceService::detectCollectionNames`) одним `aggregate` на первой коллекции с `$unionWith` на остальные и группируют один раз. Коллекций не больше 25 (период до суток), поэтому одна пачка; лимит стадий MongoDB (1000) далеко.

- `findGroups(...)`: `$group` по `_id` из выбранных полей (`sid`, `tp`, `st`, `$dateTrunc` от `lat` с `unit: hour` или `unit: minute, binSize: 10`), `count`, `$avg`, `$percentile` (p95, `approximate`), `$max` по `dur`, `$top` по `dur` убыванию с `tid`. Сортировка и `$limit` лимит+1 — в пайплайне; объекты `TraceGroupObject`.
- `compareGroups(...)`: для `tag` — `$unwind` по `tgs` с `preserveNullAndEmptyArrays`; `$group` по `{inA: {$in: ["$st", A]}, value: <sid|tp|tgs.nm|dt.<ключ>>}` с `count`; доли и сортировка — в действии, это правило предметной области, а не хранения.

Действия `Trace\Domain\Actions\Queries\FindTraceGroupsAction` и `CompareTraceGroupsAction` вызывают `init` (поля: сервисы, `lat`, типы, теги, статусы, длительность — для сравнения статусы только при заданной группе B, иначе фильтра по статусу нет), затем репозиторий. Поля группировки — `Trace\Enums\TraceGroupFieldEnum` (`service`, `type`, `status`, `hour`, `minute10`). Имена сервисов подставляет мост `Mcp` через `FindMcpServicesAction`, как в других инструментах.

### Инструменты Mcp

- `TopTraceGroupsTool`, `CompareTraceGroupsTool` — через `McpToolTraceScopeReader`, ответы индекса через `McpToolFormatter`; мосты `FindMcpTraceGroupsAction`, `CompareMcpTraceGroupsAction` переводят исключения индекса через `McpTraceIndexExceptionTranslator`. Проверка `by` — в мостах (`McpTraceGroupByInvalidException`, `McpTraceGroupsOverlapException`), чтобы индекс не строился на неверном запросе.
- `SloggerLogsTool` — мост `FindMcpLogEntriesAction`: файлы источника из `FindLogFilesAction`, уровни — имена уровней формата источника без учёта регистра в ключи `<type>.<NAME>`, `FindLogEntriesAction` с `direction: Older`, `perPage: limit`. Исключения `McpLogSourceNotFoundException(sources)`, `McpLogLevelNotFoundException(levels)`.

### Удаление get_trace_metrics

Удаляются инструмент, мост, параметры, `McpTraceMetricsObject`, `McpTraceMetricsStepNotAllowedException` и их тесты; из `McpToolFormatter` ничего не удаляется (ответы индекса общие). Рёбра `FindMcpTraceMetricsAction → Trace` вычёркиваются из `.ai/README.md`.

### Промпты и инструкции

Шаги с рядом метрик заменяются на `top_trace_groups` по `hour`/`minute10`; обзор в методе инструкций — сначала `top_trace_groups` и `compare_trace_groups`, потом `find_traces`; добавляется шаг «логи SLogger — только для вопросов о самом SLogger».

## Risks / Trade-offs

- [`$percentile` approximate на малых группах неточен] → в ответе ещё `avg` и `max`; это та же функция, что у графика UI.
- [Группировка без фильтра по сервису за сутки читает все трейсы периода] → индекс по `lat` и `$match` по периоду; период до 24 часов.
- [`compare_trace_groups` по `data.<ключ>` с высокой кардинальностью (id, uri с параметрами)] → 30 строк по перевесу, `truncated`; ключ выбирает модель.
- [Логи индексируются долго на больших файлах] → статус `indexing` вместо ожидания, как на странице логов.

## Migration Plan

Миграций нет. Клиенты, которые вызывали `get_trace_metrics`, получат ошибку неизвестного инструмента; `tools/list` обновится по `ttlMs`.
