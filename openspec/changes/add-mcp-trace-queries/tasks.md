## 1. Общие части

- [ ] 1.1 Вынести строгий разбор ISO 8601 из `GetTraceMetricsTool` в `Mcp\Infrastructure\Tools\McpToolTimeParser`, `GetTraceMetricsTool` перевести на него; тест разбора (зона, `Z`, дробные секунды, `yesterday`, дата без времени) и `make test c=tests/Modules/Mcp/Infrastructure/Tools`
- [ ] 1.2 Добавить `McpToolFormatter::indexBuilding` и `indexError`, перевести `GetTraceMetricsTool`; `hint` упоминает `get_index_status`; `TraceMetricsToolTest` проходит
- [ ] 1.3 Добавить `McpTraceIndexExceptionTranslator` в `Mcp\Domain\Services` и перевести на него `FindMcpTraceMetricsAction`; `FindMcpTraceMetricsActionTest` проходит
- [ ] 1.4 Добавить `McpTracePeriodResolver`, `McpTracePeriodObject`, `McpTraceInvalidPeriodException`, `McpTracePeriodTooWideException`; тесты: выравнивание вниз и вверх, граница часа без сдвига, ровно 24 часа проходит, 25 — нет, `from` позже `to`, другая зона приводится к UTC
- [ ] 1.5 Добавить `McpTraceDataFilterParser` и `McpTraceDataFilterInvalidException`; тесты на каждый оператор (числа, логика, строки в кавычках с пробелами, `exists`, `missing`, `is null`, `is not null`), префикс `dt.`, ошибки формата

## 2. Мосты

- [ ] 2.1 `FindMcpTraceFacetsAction` с параметрами и `McpTraceFacetsObject`; тесты с моками действий `Trace`: какие фильтры получает каждый фасет, сортировка, обрезка с `truncated`, строящийся индекс прерывает ответ
- [ ] 2.2 `FindMcpTracesAction` с параметрами и `McpTraceTagsWithDataFilterException`; тесты: сборка `TraceFindParameters` (сервис, период, фильтры, `data`, `perPage: 20`, без `hasProfiling`), теги с фильтром по `data` отклоняются до вызова, перевод исключений индекса
- [ ] 2.3 `FindMcpTraceDataFieldsAction` с `McpTraceDataFieldsObject`; тесты: обход дерева до листьев, порядок первого появления без повторов, лимит 200, нет трейсов
- [ ] 2.4 `FindMcpIndexStatusAction` с `McpIndexStatusObject` и `FindMcpDynamicIndexesAction`; тесты статусов `building` (с прогрессом и без), `ready`, `error`, `not_found`
- [ ] 2.5 Вписать новые рёбра `Mcp → Trace` в `.ai/README.md` → Cross-Module Dependencies; `make code-analise-deptrac`

## 3. Инструменты

- [ ] 3.1 Добавить `mcp.facets_limit` (`MCP_FACETS_LIMIT`, 50) в `config/mcp.php`, `.env.example` и `McpSettingsObject`
- [ ] 3.2 `TraceFacetsTool`, `FindTracesTool`, `ListTraceDataFieldsTool`: схемы, правило периода, проверка сервиса, ошибки из спеки, ответы; регистрация после `GetTraceMetricsTool`
- [ ] 3.3 `GetIndexStatusTool`, `ListDynamicIndexesTool` (часы из имён коллекций); регистрация следом
- [ ] 3.4 Тесты инструментов на сценарии спеки `mcp-trace-queries` в `tests/Modules/Mcp/Infrastructure/Tools/TraceQueryToolsTest.php` и `IndexToolsTest.php`; `make test c=tests/Modules/Mcp`
- [ ] 3.5 `McpEndpointHttpTest`: `tools/list` содержит новые инструменты в порядке регистрации

## 4. Промпты

- [ ] 4.1 `InvestigateErrorsPrompt`, `InvestigateLatencyPrompt`, `ExplainIncidentPrompt`, `ExplainTracePrompt`, регистрация в `McpPromptRegistry`
- [ ] 4.2 Тесты: список и аргументы, подстановка, необязательный `type` не попадает в текст; `McpEndpointHttpTest::testPrompts` переписать под непустой список и `-32602` без обязательного аргумента

## 5. Инструкции и документация

- [ ] 5.1 `resources/mcp/instructions.md`: шаги с `trace_facets`, `find_traces`, `list_trace_data_fields`, `list_dynamic_indexes`, правило периода, `get_index_status`
- [ ] 5.2 Раздел MCP Server в `.ai/README.md`: новые инструменты и промпты

## 6. Проверка

- [ ] 6.1 `make check`
- [ ] 6.2 `make oa-generate` не нужен: admin API не менялся, `storage/api` и `frontend/src/api-schema` без изменений
- [ ] 6.3 Проверка через `POST /mcp` на инсталляции `local` после `make sconcur-reload`: `trace_facets` и `find_traces` за час до `ready`, `find_traces` с `data_filter` и `data_fields` по ключам из `list_trace_data_fields`, `get_index_status` по `index_id` из `index_building`, `list_dynamic_indexes`, `prompts/get` для `investigate_errors`; остановить запущенные процессы
