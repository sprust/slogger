## ADDED Requirements

### Requirement: Список и получение промптов

`prompts/list` SHALL возвращать список промптов с `ttlMs` и `cacheScope: "private"`; состав промптов описан в спеке `mcp-prompts`. `prompts/get` с неизвестным именем SHALL давать ошибку `-32602`.

#### Scenario: Список промптов
- **WHEN** клиент вызывает `prompts/list`
- **THEN** ответ содержит промпты из `mcp-prompts`, `ttlMs` и `cacheScope` `"private"`

#### Scenario: Неизвестный промпт
- **WHEN** клиент вызывает `prompts/get` с именем `drop_everything`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`

## REMOVED Requirements

### Requirement: Промпты
**Reason**: Требование закрепляло пустой список промптов; теперь промпты есть, и их состав описан в `mcp-prompts`.
**Migration**: Поведение `prompts/list` и `prompts/get` описывает требование «Список и получение промптов».
