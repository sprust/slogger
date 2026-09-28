## 1. Mcp: числовые аргументы инструментов

- [x] 1.1 Добавить `McpToolPropertyTypeEnum::Number`, его JSON Schema (`number`, `minimum`/`maximum`) и правило `numeric` в `McpToolSchemaCompiler`, `floatNull` в `McpToolArguments`; дополнить `McpToolSchemaCompilerTest` (схема и правила; отказ на строке проверяется в `McpEndpointHttpTest`, задача 3.4) и прогнать `make test c=tests/Modules/Mcp/Infrastructure/Protocol`

## 2. Mcp: мост к графику трейсов

- [x] 2.1 Добавить `Mcp\Parameters\FindMcpTraceMetricsParameters` (пресет, шаг или `null`, метрики, `to`, `serviceIds`, `types`, `tags`, `statuses`, диапазоны `duration`/`memory`/`cpu`) и исключения `McpTraceMetricsStepNotAllowedException`, `McpTraceIndexBuildingException`, `McpTraceIndexFailedException` в `Mcp\Domain\Exceptions`
- [x] 2.2 Добавить мост `Mcp\Domain\Actions\Bridges\FindMcpTraceMetricsAction`: таблица шагов по умолчанию, проверка шага через `MakeTraceTimestampPeriodsAction`, сборка `FindTraceTimestampsParameters` без `data`/`dataFields`/`traceIds`/`hasProfiling`, вызов `FindTraceTimestampsAction`, перевод исключений `Trace` в исключения `Mcp`
- [x] 2.3 Добавить `tests/Modules/Mcp/Domain/Actions/FindMcpTraceMetricsActionTest.php` с моками действий `Trace`: шаг по умолчанию для каждого из шести пресетов, недопустимый шаг со списком допустимых, фильтры и метрики доходят до `FindTraceTimestampsParameters` без изменений, три исключения `Trace` превращаются в исключения `Mcp` с `indexId` и текстом; прогнать `make test c=tests/Modules/Mcp/Domain`
- [x] 2.4 Вписать рёбра `Mcp → Trace` (`FindMcpTraceMetricsAction` → `Trace\Domain\Actions\Queries\FindTraceTimestampsAction`, `Trace\Domain\Actions\MakeTraceTimestampPeriodsAction`, `Trace\Domain\Exceptions\TraceDynamicIndex*Exception`, `Trace\Parameters\FindTraceTimestampsParameters`, `Trace\Enums\TraceTimestampPeriodEnum`, `TraceTimestampEnum`, `TraceMetricFieldEnum`; `Infrastructure\Tools` → `Trace\Entities\Trace\Timestamp`) в `.ai/README.md` → Cross-Module Dependencies; проверить `make code-analise-deptrac`

## 3. Mcp: инструмент

- [x] 3.1 Добавить `GetTraceMetricsTool` (`get_trace_metrics`):
  - схема аргументов: `period` — перечисление шести пресетов; `step` — перечисление шагов; `metrics` — перечисление; списки фильтров до 20 значений; диапазоны — `Number`;
  - разбор `to` с ошибкой `invalid_time`, проверка сервисов через `FindMcpServicesAction` с ошибкой `service_not_found`;
  - перевод исключений моста в статус `index_building` (`index_id`, `retry_after_seconds: 10`, `hint`) и ошибки `index_error`, `step_not_allowed`;
  - ответ `ready` по спеке;
  - описание: строит индекс в фоне, объясняет переиспользование индекса.
- [x] 3.2 Зарегистрировать инструмент в `McpServiceProvider::TOOLS` после `GetDataRangeTool`
- [x] 3.3 Добавить `tests/Modules/Mcp/Infrastructure/Tools/TraceMetricsToolTest.php` на сценарии спеки:
  - ответ `ready`: `status`, `period`, `step`, `from`, `to`, `metrics`, `points`;
  - `count` из `sum` целым, объект шести индикаторов для остальных метрик;
  - пустой шаг с `count: 0`, только запрошенные метрики;
  - `index_building` без `isError`, `index_error`, `step_not_allowed`, `service_not_found`, `invalid_time`.

  Прогнать `make test c=tests/Modules/Mcp/Infrastructure/Tools`.
- [x] 3.4 Дополнить `McpEndpointHttpTest`: `tools/call` `get_trace_metrics` с `period: "7 days"` даёт `-32602` с полем `period`, `tools/list` содержит инструмент с `readOnlyHint: true`; прогнать `make test c=tests/Modules/Mcp/Infrastructure/Http`
- [x] 3.5 Добавить в `resources/mcp/instructions.md` шаг `get_trace_metrics` после `get_data_range`. Добавить правило: для сравнения менять значения фильтров, а не их набор, шаг и период; на `index_building` повторять тот же вызов. Упомянуть инструмент в разделе MCP Server `.ai/README.md`

## 4. Связанные планы

- [x] 4.1 Убрать `trace_timeseries` из `openspec/changes/add-mcp-trace-queries/proposal.md` со ссылкой на `add-mcp-trace-metrics` (сделано при планировании; у того change пока есть только proposal, `openspec validate` на нём падает из-за отсутствия спек — так было и до правки)
- [x] 4.2 При архивации поправить `## Purpose` в `openspec/specs/mcp-tools/spec.md`: инструменты читают сервисы, инциденты и трейсы, а `get_trace_metrics` строит динамический индекс, как график UI

## 5. Проверка

- [x] 5.1 Запустить `make check` и убедиться, что проходят PHPStan, Deptrac, CS Fixer и все тесты
- [x] 5.2 Убедиться, что `make oa-generate` не нужен: HTTP-слой admin API не менялся, `git status` не показывает изменений в `storage/api` и `frontend/src/api-schema`
- [x] 5.3 Проверка через MCP-клиент на инсталляции `local`:
  - перезапустить sconcur-воркер;
  - вызвать `get_trace_metrics` с `period: "1 hour"` и одним сервисом, дождаться `ready` повтором, сверить числа с графиком страницы трейсов за тот же час;
  - вызвать с другим `types` и убедиться, что на странице динамических индексов не появился новый индекс;
  - остановить запущенные процессы.

  Сделано 2026-09-28 прямыми вызовами `POST /mcp` с токеном `slogger-local`: сверка по одному сервису за час с `to` 20:20 UTC против `FindTraceTimestampsAction`, который вызывает контроллер графика, — 61 точка, 3989 трейсов, max duration 13.366336 с обеих сторон; смена `service_ids` и `types` при том же наборе фильтров дала `ready` сразу, число индексов 3 → 3. Воркеры перезапущены через `make sconcur-reload`, новых процессов не запускалось.
