## 1. Удаление get_trace_metrics

- [x] 1.1 Удалить `GetTraceMetricsTool`, `FindMcpTraceMetricsAction`, `FindMcpTraceMetricsParameters`, `McpTraceMetricsObject`, `McpTraceMetricsStepNotAllowedException`, их тесты и регистрацию; вычеркнуть рёбра из `.ai/README.md`; `make test c=tests/Modules/Mcp` и `make code-analise-stan`

## 2. Агрегации в Trace

- [x] 2.1 `TraceGroupFieldEnum`, параметры `TraceFindGroupsParameters`, `TraceCompareGroupsParameters`, объекты `TraceGroupObject`, `TraceGroupComparisonRowObject`, `TraceGroupComparisonObject`
- [x] 2.2 `TraceGroupsRepository::findGroups` и `compareGroups` с `$unionWith` по коллекциям периода; тест построения пайплайна (поля группы, `$dateTrunc`, `$top`, `$unwind` для тегов, `$unionWith`)
- [x] 2.3 `FindTraceGroupsAction`, `CompareTraceGroupsAction` (init индекса, доли и сортировка сравнения), регистрация в `TraceServiceProvider`; тесты действий с моком репозитория и инициализатора

## 3. Инструменты групп

- [x] 3.1 Мосты `FindMcpTraceGroupsAction`, `CompareMcpTraceGroupsAction`, исключения `by` и пересечения групп; тесты мостов
- [x] 3.2 `TopTraceGroupsTool`, `CompareTraceGroupsTool`, регистрация после `list_trace_data_fields`; тесты инструментов на сценарии спеки

## 4. Логи SLogger

- [x] 4.1 Мост `FindMcpLogEntriesAction` и исключения источника и уровня; тесты моста
- [x] 4.2 `SloggerLogsTool`, регистрация последним; тесты инструмента

## 5. Промпты, инструкции, документация

- [x] 5.1 Промпты без `get_trace_metrics`, тесты промптов и `McpEndpointHttpTest`
- [x] 5.2 `resources/mcp/instructions.md`: составные инструменты первыми, логи SLogger; `.ai/README.md` — MCP Server и рёбра `Mcp → Trace`, `Mcp → Logs`

## 6. Проверка

- [x] 6.1 `make check`
- [x] 6.2 `make oa-generate` не нужен: admin API не менялся
- [x] 6.3 На `local` через `POST /mcp` после `make sconcur-reload`: `top_trace_groups` по `service`+`type` и по `minute10` со `statuses: ["failed"]`, `compare_trace_groups` по `type` и по `data.<ключ>`, `slogger_logs` по `Laravel`; `tools/list` без `get_trace_metrics`
