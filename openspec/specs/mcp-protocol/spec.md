# mcp-protocol Specification

## Purpose
Эндпоинт MCP, через который LLM-клиенты работают с данными SLogger: транспорт Streamable HTTP ревизии протокола `2026-07-28`, проверка запросов, методы протокола, инструкции сервера и имя инсталляции.

## Requirements

### Requirement: Эндпоинт и транспорт

Система SHALL принимать сообщения MCP на единственном эндпоинте `/mcp` методом `POST`: одно сообщение JSON-RPC 2.0 в теле. Ответ на запрос SHALL иметь `Content-Type: application/json`. Система SHALL NOT открывать потоки SSE, выдавать `Mcp-Session-Id` и хранить состояние между запросами.

#### Scenario: Запрос получает JSON-ответ
- **WHEN** клиент отправляет корректный запрос `tools/list` с валидным токеном и заголовками
- **THEN** система отвечает `200` с `Content-Type: application/json` и объектом JSON-RPC с тем же `id`

#### Scenario: Уведомление
- **WHEN** клиент отправляет сообщение JSON-RPC без `id`
- **THEN** система отвечает `202` без тела

#### Scenario: GET и DELETE
- **WHEN** клиент отправляет `GET /mcp` или `DELETE /mcp`
- **THEN** система отвечает `405`

#### Scenario: Заголовок сессии игнорируется
- **WHEN** запрос содержит заголовок `Mcp-Session-Id`
- **THEN** система обрабатывает запрос как обычно и не возвращает `Mcp-Session-Id` в ответе

### Requirement: Проверка Origin

Если запрос содержит заголовок `Origin`, система SHALL сравнить его хост с хостом `APP_URL` и при несовпадении ответить `403` до любой другой обработки. Запрос без `Origin` проверку проходит.

#### Scenario: Чужой Origin
- **WHEN** запрос содержит `Origin: https://evil.example`, а `APP_URL` указывает на другой хост
- **THEN** система отвечает `403`

#### Scenario: Нет Origin
- **WHEN** запрос не содержит `Origin`
- **THEN** проверка Origin не мешает обработке запроса

### Requirement: Аутентификация

Каждый запрос к `/mcp` SHALL нести заголовок `Authorization: Bearer <token>` с токеном включённого подключения MCP. Иначе система SHALL ответить `401`.

#### Scenario: Нет токена
- **WHEN** запрос не содержит заголовка `Authorization`
- **THEN** система отвечает `401`

#### Scenario: Неизвестный токен
- **WHEN** токен не совпадает ни с одним подключением
- **THEN** система отвечает `401`

### Requirement: Разбор сообщения

Система SHALL отвечать `400` с ошибкой JSON-RPC без `id`: `-32700`, если тело не JSON; `-32600`, если тело — массив (batch) или не объект запроса либо уведомления JSON-RPC 2.0.

#### Scenario: Битый JSON
- **WHEN** тело запроса не разбирается как JSON
- **THEN** система отвечает `400` с ошибкой `-32700`

#### Scenario: Batch
- **WHEN** тело запроса — массив сообщений JSON-RPC
- **THEN** система отвечает `400` с ошибкой `-32600`

#### Scenario: Не JSON-RPC
- **WHEN** в теле нет `"jsonrpc": "2.0"` или нет строкового `method`
- **THEN** система отвечает `400` с ошибкой `-32600`

### Requirement: Заголовки запроса

Для запроса (сообщения с `id`) система SHALL проверить заголовки `MCP-Protocol-Version` и `Mcp-Method`, а для `tools/call` и `prompts/get` ещё и `Mcp-Name`. Значения, закодированные как `=?base64?...?=`, SHALL декодироваться перед сравнением. Заголовок отсутствует, содержит недопустимые символы или не совпадает с телом (`MCP-Protocol-Version` — с `params._meta["io.modelcontextprotocol/protocolVersion"]`, `Mcp-Method` — с `method`, `Mcp-Name` — с `params.name`) — система SHALL ответить `400` с ошибкой `-32020` (`HeaderMismatch`).

#### Scenario: Нет заголовка версии
- **WHEN** запрос `tools/list` не содержит `MCP-Protocol-Version`
- **THEN** система отвечает `400` с ошибкой `-32020`

#### Scenario: Метод в заголовке не совпадает с телом
- **WHEN** `Mcp-Method: tools/list`, а в теле `"method": "tools/call"`
- **THEN** система отвечает `400` с ошибкой `-32020`

#### Scenario: Имя инструмента в Base64
- **WHEN** `Mcp-Name` содержит `=?base64?...?=`, и после декодирования значение совпадает с `params.name`
- **THEN** проверка заголовков проходит

#### Scenario: Нет версии в _meta
- **WHEN** заголовок `MCP-Protocol-Version` есть, а `params._meta` не содержит версии протокола
- **THEN** система отвечает `400` с ошибкой `-32020`

### Requirement: Версия протокола

Система SHALL поддерживать только ревизию `2026-07-28`. На запрос с другой версией система SHALL ответить `400` с ошибкой `-32022` и `data` вида `{"supported": ["2026-07-28"], "requested": "<версия из запроса>"}`.

#### Scenario: Неподдерживаемая версия
- **WHEN** заголовок и `_meta` согласованно указывают версию `2025-11-25`
- **THEN** система отвечает `400` с ошибкой `-32022`, `data.supported` равно `["2026-07-28"]`, `data.requested` равно `"2025-11-25"`

#### Scenario: Рукопожатие старого клиента
- **WHEN** клиент отправляет `initialize` без заголовка `MCP-Protocol-Version`
- **THEN** система отвечает `400` с ошибкой `-32020`, и текст ошибки называет поддерживаемую версию `2026-07-28`

### Requirement: Методы протокола

Система SHALL поддерживать методы `server/discover`, `tools/list`, `tools/call`, `prompts/list`, `prompts/get`. На любой другой метод, включая `initialize` и `ping` с корректными заголовками, система SHALL ответить `404` с ошибкой `-32601`. Каждый `result` SHALL содержать `resultType: "complete"` и `_meta["io.modelcontextprotocol/serverInfo"]` с `name` и `version`.

#### Scenario: Неизвестный метод
- **WHEN** корректный по заголовкам запрос вызывает метод `resources/list`
- **THEN** система отвечает `404` с ошибкой `-32601`

#### Scenario: Общие поля результата
- **WHEN** любой поддерживаемый метод завершается успешно
- **THEN** `result.resultType` равно `"complete"`, а `result._meta["io.modelcontextprotocol/serverInfo"].name` равно `slogger-<server_name>`

### Requirement: server/discover

Ответ на `server/discover` SHALL содержать `supportedVersions: ["2026-07-28"]`, `capabilities` с ключами `tools` и `prompts`, `instructions`, `ttlMs` и `cacheScope: "private"`.

#### Scenario: Discover
- **WHEN** клиент вызывает `server/discover`
- **THEN** ответ содержит `supportedVersions` `["2026-07-28"]`, `capabilities.tools`, `capabilities.prompts`, непустые `instructions`, `ttlMs` и `cacheScope` `"private"`

### Requirement: Список инструментов

`tools/list` SHALL возвращать все инструменты в неизменном порядке между вызовами, у каждого `name`, `title`, `description`, `inputSchema` (JSON Schema, объект) и `annotations.readOnlyHint: true`. Ответ SHALL содержать `ttlMs` и `cacheScope: "private"`. Описание инструмента, который что-то строит в фоне, SHALL говорить об этом и о статусе, которым он отвечает, пока строит: `get_trace_tree` — кэш дерева (`tree_building`), `search_slogger_logs` — индекс файлов логов (`indexing`). Остальные инструменты отвечают сразу, и их описания о фоне не говорят.

#### Scenario: Список инструментов
- **WHEN** клиент дважды вызывает `tools/list`
- **THEN** оба ответа содержат одинаковый список инструментов в одинаковом порядке, у каждого `annotations.readOnlyHint` равно `true`

### Requirement: Вызов инструмента

`tools/call` SHALL проверять `params.name` и `params.arguments` по `inputSchema` инструмента до вызова. Неизвестный инструмент или аргументы не по схеме SHALL давать ошибку JSON-RPC `-32602` с описанием, какой аргумент неверен. Успешный вызов SHALL возвращать `content` с одним элементом `{"type": "text", "text": <JSON ответа>}` и тот же ответ объектом в `structuredContent`. Ошибка, которую модель может исправить (например, трейс не найден), SHALL возвращаться как `result` с `isError: true` и текстом, объясняющим, что сделать, а не как ошибка JSON-RPC.

#### Scenario: Неизвестный инструмент
- **WHEN** `tools/call` вызывает инструмент `drop_everything`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`

#### Scenario: Аргумент не по схеме
- **WHEN** `tools/call` вызывает `get_trace` без `trace_id`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`, в тексте которой названо поле `trace_id`

#### Scenario: Ошибка инструмента
- **WHEN** `tools/call` вызывает `get_trace` с `trace_id`, которого нет
- **THEN** ответ — `result` с `isError: true` и текстом ошибки, без поля `error` JSON-RPC

#### Scenario: Успешный вызов
- **WHEN** `tools/call` вызывает `get_services` без аргументов
- **THEN** `result.content[0].type` равно `"text"`, а `result.structuredContent` равен разобранному `result.content[0].text`

### Requirement: Имя инсталляции

Система SHALL брать имя инсталляции из `MCP_SERVER_NAME`, по умолчанию из `APP_ENV`. Имя SHALL соответствовать `^[a-z0-9-]{1,32}$`, иначе приложение SHALL падать при старте, а не подменять имя. Имя сервера — `slogger-<server_name>`, заголовок — `SLogger (<server_name>)`.

#### Scenario: Имя по умолчанию
- **WHEN** `MCP_SERVER_NAME` не задан, а `APP_ENV=local`
- **THEN** `serverInfo.name` равно `slogger-local`

#### Scenario: Неверное имя
- **WHEN** `MCP_SERVER_NAME=Prod Server`
- **THEN** приложение при старте бросает исключение

### Requirement: Инструкции сервера

`instructions` SHALL начинаться с блока, который называет инсталляцию (`server_name` и `APP_URL`), говорит, что несколько инсталляций могут быть подключены одновременно и не делят данные, и требует спрашивать инсталляцию, если она не названа, не смешивать id разных инсталляций и называть инсталляцию в ответе. Дальше SHALL идти порядок работы с инструментами этого изменения. Текст инструкций — на английском.

#### Scenario: Блок инсталляции
- **WHEN** клиент вызывает `server/discover` на инсталляции `prod` с `APP_URL=https://slogger.example.com`
- **THEN** `instructions` начинаются с текста, содержащего `"prod"` и `https://slogger.example.com`

### Requirement: Список и получение промптов

`prompts/list` SHALL возвращать список промптов с `ttlMs` и `cacheScope: "private"`; состав промптов описан в спеке `mcp-prompts`. `prompts/get` с неизвестным именем SHALL давать ошибку `-32602`.

#### Scenario: Список промптов
- **WHEN** клиент вызывает `prompts/list`
- **THEN** ответ содержит промпты из `mcp-prompts`, `ttlMs` и `cacheScope` `"private"`

#### Scenario: Неизвестный промпт
- **WHEN** клиент вызывает `prompts/get` с именем `drop_everything`
- **THEN** ответ содержит ошибку JSON-RPC `-32602`

### Requirement: Трейсинг запросов к `/mcp`

При включённом трейсинге запросов (`SLOGGER_LOG_REQUESTS_ENABLED`) SLogger SHALL трейсить запросы к `/mcp` так же, как запросы admin API. Трейс успешного ответа SHALL иметь теги `/mcp`, метод JSON-RPC и, если он есть, `params.name` — имя инструмента или промпта. Трейс ответа `4xx`/`5xx` SHALL иметь только тег `/mcp`: его тело не прошло сверку с заголовками. Тело ответа SHALL NOT записываться в трейс.

#### Scenario: Вызов инструмента
- **WHEN** клиент вызывает `tools/call` с `name` `get_services`, и ответ `200`
- **THEN** у трейса запроса теги `/mcp`, `tools/call`, `get_services`, а данных ответа в трейсе нет

#### Scenario: Отклонённый запрос
- **WHEN** запрос к `/mcp` отклонён с `400` из-за расхождения заголовков и тела
- **THEN** у трейса запроса только тег `/mcp`
