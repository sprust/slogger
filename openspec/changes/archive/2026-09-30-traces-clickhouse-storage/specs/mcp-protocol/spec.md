## MODIFIED Requirements

### Requirement: Список инструментов

`tools/list` SHALL возвращать все инструменты в неизменном порядке между вызовами, у каждого `name`, `title`, `description`, `inputSchema` (JSON Schema, объект) и `annotations.readOnlyHint: true`. Ответ SHALL содержать `ttlMs` и `cacheScope: "private"`. Описание инструмента, который что-то строит в фоне, SHALL говорить об этом и о статусе, которым он отвечает, пока строит: `get_trace_tree` — кэш дерева (`tree_building`), `search_slogger_logs` — индекс файлов логов (`indexing`). Остальные инструменты отвечают сразу, и их описания о фоне не говорят.

#### Scenario: Список инструментов
- **WHEN** клиент дважды вызывает `tools/list`
- **THEN** оба ответа содержат одинаковый список инструментов в одинаковом порядке, у каждого `annotations.readOnlyHint` равно `true`
