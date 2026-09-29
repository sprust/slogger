## Why

Модель отвечает на обзорные вопросы («каких трейсов и по каким сервисам больше», «где и когда падает», «чем упавшие отличаются от остальных») десятком вызовов `find_traces` и `trace_facets`: фасеты не разбивают по сервисам и не сочетают поля, а ряд `get_trace_metrics` строит отдельный индекс на каждый шаг и не группирует. Составные инструменты фазы 3 из черновика отвечают на такие вопросы одной агрегацией на одном индексе. После них `get_trace_metrics` не нужен: ряд по времени даёт группировка по часу или по 10 минутам, а его пресеты и шаги живут по своим правилам, отдельно от правила периода остальных инструментов. Вопросы о работе самого SLogger модель сейчас не может проверить по его логам.

## What Changes

- **BREAKING** `get_trace_metrics` удаляется вместе с мостом, параметрами, исключениями шага и объектом результата. Исправление графика страницы трейсов (корзины длиннее часа) остаётся: это поведение UI.
- Новый инструмент `top_trace_groups` — трейсы за период, сгруппированные по одному–трём полям из `service`, `type`, `status`, `hour`, `minute10`: количество, средняя, p95 и максимальная длительность, пример — самый долгий трейс группы. Фильтры и правило периода — как у `find_traces`, поэтому индекс общий с ним.
- Новый инструмент `compare_trace_groups` — две группы трейсов по статусам (`group_a_statuses`, `group_b_statuses`, без второй — все остальные) распределены по `type`, `service`, `tag` или ключу `data`, с количеством и долей в каждой группе, отсортированы по перевесу группы A.
- Новый инструмент `slogger_logs` — записи логов самого SLogger из модуля Logs по источнику, уровням, периоду и тексту, новые первыми.
- Правило периода, статус `index_building` и сервисы (`service_ids`) распространяются на `top_trace_groups` и `compare_trace_groups`; подсказка `period_too_wide` ссылается на `top_trace_groups` по часам.
- Промпты и инструкции сервера: обзор — сначала составные инструменты, потом `find_traces`; окно по времени — `top_trace_groups` по `hour` или `minute10`.

## Capabilities

### New Capabilities

Нет.

### Modified Capabilities

- `mcp-tools`: удаляется требование `get_trace_metrics`; добавляется `slogger_logs`; меняется список допустимых побочных эффектов в «Общий формат ответа».
- `mcp-trace-queries`: добавляются `top_trace_groups` и `compare_trace_groups`; меняются «Правило периода» и «Статус построения индекса».
- `mcp-prompts`: меняется «Содержание промпта» — шаги без `get_trace_metrics`.

## Impact

- Модули и слои Deptrac:
  - `Trace` — `Domain/Actions/Queries` (`FindTraceGroupsAction`, `CompareTraceGroupsAction`), `Parameters`, `Entities`, `Enums` (`TraceGroupFieldEnum`), `Repositories` (`TraceGroupsRepository`: агрегация по коллекциям периода через `$unionWith`), `Infrastructure` (регистрация);
  - `Mcp` — мосты к `Trace` и `Logs`, инструменты, удаление кода `get_trace_metrics`, промпты, `McpServiceProvider`;
  - `Logs` не меняется: мост вызывает `FindLogFilesAction`, `FindLogEntriesAction` и читает имена уровней через `LogFormatRegistry`.
- Новые рёбра `Mcp → Trace`, `Mcp → Logs` вписываются в `.ai/README.md`, рёбра `get_trace_metrics` удаляются.
- MongoDB: одна агрегация на период не длиннее 24 часов (до 25 коллекций в `$unionWith`), индекс — тот же набор полей, что у `find_traces`.
- Admin API, OpenAPI, фронтенд и receiver не меняются.
