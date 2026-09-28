## Context

Выборки трейсов в `Trace`, все вызывают `TraceDynamicIndexInitializer::init` до запроса:

- `FindTracesAction::handle(TraceFindParameters): TraceItemObjects` — страница до 20 трейсов, сортировка `lat` по убыванию. `data->fields` дают `additionalFields` (`TraceDataAdditionalFieldObject(key, values)`). Индекс включает `lat` всегда (`needLoggedAt: true`).
- `FindTypesAction`, `FindStatusesAction`, `FindTagsAction` — `TraceStringFieldObject(name, count)[]`. Каждое добавляет в индекс своё поле (`tp`, `st` через заглушку `['stub']`, для тегов — `tgs.nm` только при поиске по тексту). Поэтому три фасета — до трёх разных индексов.
- `FindTraceDetailAction::handle(traceId): ?TraceDetailObject`: `data` — дерево `TraceDataObject(key, value, children)`, где `key` — полный путь через точку.
- Фильтр по `data` — `TraceDataFilterParameters(filter, fields)`; условие `TraceDataFilterItemParameters(field, null, exists, numeric, string, boolean)`. `field` — ключ Mongo с префиксом `dt.`, как его собирает фронтенд (`dt.${field}`); `fields` — пути без префикса.
- Индексы: `FindTraceDynamicIndexAction::handle(id): ?TraceDynamicIndexObject`, `FindTraceDynamicIndexesAction::handle()` (100 новых первыми), `FindTraceDynamicIndexStatsAction::handle(): TraceDynamicIndexStatsObject` с `indexesInProcess` — `TraceIndexInfoObject(collectionName, name, progress)` из `currentOp`.

В `Mcp` уже есть `McpTraceIndexBuildingException`, `McpTraceIndexFailedException` (из `add-mcp-trace-metrics`), `McpPromptInterface`, `McpPromptRegistry`, `prompts/list` и `prompts/get` в `McpServer`; реестр промптов в `McpServiceProvider` пуст. Разбор ISO 8601 и ответ `index_building` сейчас живут внутри `GetTraceMetricsTool`.

## Goals / Non-Goals

**Goals:**
- Модель находит трейсы и их поля за период, не строя лишних индексов: период выровнен, набор полей фильтра стабилен, готовые индексы видны.
- Ответы со статусом индекса и ошибками одинаковы во всех инструментах.

**Non-Goals:**
- Изменения модуля `Trace`, admin API и фронтенда.
- Бюджет индексов на подключение.
- Фильтры `memory`/`cpu` и `has_profiling` в `find_traces`: модели хватает длительности; добавляются, если понадобятся.

## Decisions

### Общие части инструментов

- `Mcp\Infrastructure\Tools\McpToolTimeParser` — строгий разбор ISO 8601 (перенос из `GetTraceMetricsTool`, который начинает им пользоваться).
- `McpToolFormatter::indexBuilding(string $indexId)` и `indexError(string $message)` — общий ответ статуса и ошибки индекса; `GetTraceMetricsTool` переходит на них, его `hint` упоминает `get_index_status`.
- `Mcp\Domain\Services\McpTracePeriodResolver::resolve(Carbon $from, Carbon $to): McpTracePeriodObject` — выравнивание по часу и проверки; бросает `McpTraceInvalidPeriodException` и `McpTracePeriodTooWideException(maxHours)`. Максимум 24 часа — константа сервиса.
- Проверка сервиса — существующий мост `FindMcpServicesAction`, как в `get_trace_metrics`.

Правило периода — доменное (от него зависят индексы), поэтому сервис в `Domain`, а не в инструменте. Разбор строки времени — разбор аргумента, он в `Infrastructure`.

### Фильтр по data строкой

У схемы инструментов нет списка объектов, а модели уверенно пишут выражения. `Mcp\Domain\Services\McpTraceDataFilterParser::parse(string[] $conditions): TraceDataFilterItemParameters[]` разбирает `<ключ> <оператор> <значение>` регулярными выражениями по формату спеки и добавляет к ключу префикс `dt.`; ошибка — `McpTraceDataFilterInvalidException(condition)`. Альтернатива — новый тип свойства «список объектов» в `McpToolSchemaCompiler` с вложенной схемой — отклонена: больше кода в протоколе ради одного аргумента, а ошибки модели в структуре всё равно надо объяснять текстом.

`tags` вместе с `data_filter` проверяются в мосте `find_traces` до вызова `FindTracesAction` (`McpTraceTagsWithDataFilterException`), чтобы индекс не начал строиться и не упал на `TraceDynamicIndexParallelArraysException`.

### Мосты

Каждый мост переводит исключения индекса `Trace` в исключения `Mcp` одинаково; перевод выносится в `Mcp\Domain\Services\McpTraceIndexExceptionTranslator::call(Closure)`, им же начинает пользоваться `FindMcpTraceMetricsAction`.

- `FindMcpTraceFacetsAction::handle(FindMcpTraceFacetsParameters): McpTraceFacetsObject` — три вызова по очереди: типы (фильтр `statuses`), статусы (фильтр `types`), теги (оба фильтра). Первый же строящийся индекс прерывает ответ статусом `index_building`: остальные индексы запустятся на повторном вызове. Сортировка и обрезка по `facetsLimit` — в мосте.
- `FindMcpTracesAction::handle(FindMcpTracesParameters): TraceItemObjects` — собирает `TraceFindParameters` (`serviceIds: [serviceId]`, `loggingPeriod`, фильтры, `data`, `perPage: 20`, `hasProfiling: null`).
- `FindMcpTraceDataFieldsAction::handle(FindMcpTraceDataFieldsParameters): McpTraceDataFieldsObject` — `FindTracesAction` с `types: [type]`, `perPage: 5`, затем `FindTraceDetailAction` по каждому трейсу; обход дерева `data` до листьев, ключи в порядке первого появления, не больше 200.
- `FindMcpIndexStatusAction::handle(string $indexId): McpIndexStatusObject` — индекс и статистика; `progress` — сумма `progress` записей `indexesInProcess` с именем индекса (`indexName`), делённая на число коллекций индекса; нет записей — `null`.
- `FindMcpDynamicIndexesAction::handle(): TraceDynamicIndexObject[]` — как есть; часы — из имён коллекций через `PeriodicTraceCollectionNameService::makeHourStart` нельзя (это `Repositories` чужого модуля), поэтому первый и последний час инструмент берёт из имени коллекции по формату `traces_Y_m_d_HH_HH` в своём форматтере.

### Инструменты

`TraceFacetsTool`, `FindTracesTool`, `ListTraceDataFieldsTool`, `GetIndexStatusTool`, `ListDynamicIndexesTool` в `Mcp\Infrastructure\Tools`, регистрация в `McpServiceProvider::TOOLS` после `GetTraceMetricsTool` в этом порядке. Лимит фасетов — `mcp.facets_limit` (`MCP_FACETS_LIMIT`, 50) через `McpSettingsObject`, как `treeNodesLimit`.

### Промпты

`Mcp\Infrastructure\Prompts\InvestigateErrorsPrompt`, `InvestigateLatencyPrompt`, `ExplainIncidentPrompt`, `ExplainTracePrompt` реализуют `McpPromptInterface`; текст — английский, шаги ссылаются на инструменты по именам. Реестр в `McpServiceProvider` получает их в порядке спеки.

### Инструкции сервера

Метод дополняется: `trace_facets` для обзора типов и статусов в окне, найденном по `get_trace_metrics`; `find_traces` для самих трейсов; `list_trace_data_fields` перед фильтром по `data`; `list_dynamic_indexes` — чтобы подобрать фильтры под готовые индексы. Правила: период не длиннее 24 часов, выровнен по часу — сдвигать окно внутри часа бесплатно; `index_building` — повторить тот же вызов или спросить `get_index_status`.

## Risks / Trade-offs

- [`trace_facets` строит до трёх индексов за вызов, и модель трижды видит `index_building`] → подсказка просит повторять тот же вызов; уже готовые индексы не перестраиваются.
- [Модель пишет `data_filter` не по формату] → ошибка с условием и форматом, описание инструмента с примерами.
- [`list_trace_data_fields` делает до шести запросов] → маленький `perPage`, детали по трейсам только из одной страницы.
- [Без бюджета модель может перебрать много наборов полей] → период ограничен, в инструкциях и описаниях правило переиспользования, индексы видны в UI и истекают через 12 часов.
- [Прогресс индекса приблизительный] → он из `currentOp` по коллекциям, где идёт построение; готовые коллекции в нём не учитываются, поэтому это подсказка, а не обещание.

## Migration Plan

Миграций нет. Откат — убрать инструменты из `McpServiceProvider::TOOLS` и промпты из реестра.
