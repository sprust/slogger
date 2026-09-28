## Context

Мотивация — в `proposal.md`, поведение — в `specs/`. Здесь только то, что определяет, как это встроить в проект.

- Бэкенд работает под SConcur: каждый HTTP-запрос — отдельный Fiber в долгоживущем воркере, за nginx на `:8097`. Блок `location /` в `docker/nginx/templates/default.conf.template` отдаёт любой путь воркерам, так что `/mcp` доходит до приложения без правок nginx. Префикс `/app/` занят websocket-пулом.
- Маршруты регистрируются в `App\Providers\RouteServiceProvider::boot()`, а не в модулях: `routes/admin-api.php` идёт с префиксом `admin-api` и middleware `AuthMiddleware` + `SLoggerLaravel\Middleware\HttpMiddleware`.
- `AuthMiddleware` берёт Bearer-токен, кладёт пользователя в `setUserResolver`, в `terminate` продлевает токен через `TouchUserTokenAction`. Схема MCP-подключений повторяет её.
- Токен сервиса генерирует `ServiceRepository::create` через `Str::random(50)` и хранит открытым текстом. Для `mcps` токен генерирует Action, репозиторий только сохраняет.
- `FindIncidentsAction` фильтрует только по статусу и смотрителю (`FindIncidentsParameters`), по сервису — нет.
- `FindTraceTreeAction::handle(traceId, fresh, isChild)` сам находит корень, при отсутствии состояния запускает построение кэша и возвращает состояние без узлов. Прочитать состояние, не запуская построения, сейчас нельзя.
- Готового Action'а «первый и последний час с трейсами» нет; список коллекций отдаёт `PeriodicTraceService::detectCollectionNames()`.
- Deptrac подхватывает новый модуль сам: слои задаются регуляркой по `app/Modules/\w+/...`.

## Goals / Non-Goals

**Goals:**

- Весь MCP-код — в модуле `app/Modules/Mcp` под Deptrac.
- Зависимости в чужие модули идут только из `Mcp` и только через именованные мосты, одним списком в `.ai/README.md`.
- Протокол — своя реализация ревизии `2026-07-28`, одна версия.

**Non-Goals:**

- Динамические индексы трейсов, `McpQueryGuard`, бюджет индексов, `get_index_status`, `list_dynamic_indexes`, запросы по трейсам за период, промпты — изменение `add-mcp-trace-queries`.
- Профилирование трейсов: его планируется убрать из SLogger, MCP его не отдаёт.
- Ревизии протокола до `2026-07-28`, SSE, `subscriptions/listen`, MRTR, расширение tasks.
- OAuth и привязка подключения к пользователю или сервисам.
- Глобальный выключатель MCP на инсталляции: доступ управляется подключениями.

## Decisions

### D1. Модуль `Mcp`

```text
app/Modules/Mcp/
├── Domain/
│   ├── Actions/
│   │   ├── Mutations/   CreateMcpAction, UpdateMcpAction, RegenerateMcpTokenAction,
│   │   │                DeleteMcpAction, TouchMcpAction
│   │   ├── Queries/     FindMcpsAction, FindMcpAction, FindMcpByTokenAction
│   │   └── Bridges/     мосты в Service, Watcher, Trace (D3)
│   └── Exceptions/
├── Entities/            McpObject, объекты результатов мостов
├── Parameters/          McpCreateParameters, McpUpdateParameters, параметры мостов
├── Repositories/        McpRepository, Dto/McpDto
└── Infrastructure/
    ├── Http/            Controllers, Requests, Resources, Middlewares
    ├── Protocol/        транспорт и JSON-RPC (D4)
    ├── Tools/           инструменты (D5)
    └── McpServiceProvider.php
```

`McpServiceProvider` регистрируется в `ModulesConfig::getProviders()`.

Альтернатива из старого плана — сервер в `app/Mcp` вне Deptrac, инструменты в `Infrastructure/Mcp` каждого модуля. Отклонена: половина кода вне проверки слоёв, и каждый модуль знает про MCP.

### D2. Подключения

- Таблица MySQL `mcps`: `id`, `name` (string 255), `token` (string 50, unique), `enabled` (bool, default true), `last_used_at` (nullable), `created_at`, `updated_at`. Модель `App\Models\Mcps\Mcp` на `AbstractModel`, фабрика `database/factories/Mcps/McpFactory.php`.
- `McpRepository` — примитивы: `create(name, token)`, `find()`, `findOneById`, `findOneByToken`, `update(id, name, enabled)`, `updateToken(id, token)`, `updateLastUsedAt(id, at)`, `delete(id)`.
- Токен генерируют `CreateMcpAction` и `RegenerateMcpTokenAction` (`Str::random(50)`).
- `FindMcpByTokenAction` возвращает только включённое подключение.
- `TouchMcpAction` пишет `last_used_at`, только если прошла минута с прошлого значения.
- `McpTokenMiddleware` (`Infrastructure/Http/Middlewares`): нет токена или `FindMcpByTokenAction` вернул `null` — `abort(401)`. Иначе кладёт `McpObject` в атрибуты запроса и в `terminate` вызывает `TouchMcpAction`, как `AuthMiddleware`.
- Admin API в `routes/admin-api.php`:
  - `McpController`: `index`, `show`, `create`, `update`, `regenerateToken`, `delete`. Перевыпуск токена — мутация того же подключения, а не отдельная сущность.
  - `McpSettingsController`: `GET /mcps/settings` с `server_name` и `endpoint_url`. Это настройки инсталляции, а не подключение, поэтому отдельный контроллер.
  - `settings` объявляется до `{id}`, а `{id}` ограничен `whereNumber`.

### D3. Мосты в другие модули

Инструмент никогда не вызывает чужой модуль сам. Он вызывает мост — Action в `Mcp\Domain\Actions\Bridges`, — и только мосты ходят в `Domain` чужих модулей. Мост возвращает чужие `Entities` как есть: инструмент всё равно сам собирает компактный JSON, а копия объектов ничего не изолирует. Направление одно: `Service`, `Watcher` и `Trace` про `Mcp` не знают.

| Мост | Куда ходит |
|---|---|
| `FindMcpServicesAction` | `Service\Domain\Actions\FindServicesAction` |
| `FindMcpDataRangeAction` | `Trace\Domain\Actions\Queries\FindTraceDataRangeAction` (новый, D7) |
| `FindMcpIncidentsAction` | `Watcher\...\Queries\FindIncidentsAction`, `FindWatcherAction` |
| `FindMcpIncidentEventsAction` | `FindIncidentAction`, `FindWatcherAction`, `FindIncidentEventsAction`, `Watcher\Domain\Services\Types\WatcherTypeRegistry` |
| `FindMcpTraceAction` | `Trace\...\Queries\FindTraceDetailAction` |
| `FindMcpTraceTreeAction` | `FindTraceTreeStateAction` (новый, D6), `FindTraceTreeAction`, `FindTraceTreeChildrenAction` |
| `FindMcpTraceTreeFilteredAction` | `FindTraceTreeStateAction`, `FindTraceTreeFilteredAction` |

Кроме того, мосты используют `Entities`, `Enums` и `Parameters` этих модулей. `get_trace_data` использует `Trace\Infrastructure\Http\Resources\Data\TraceDataResource` (D5).

Все рёбра вписываются в `.ai/README.md` → Cross-Module Dependencies, раздел `Mcp → Service / Watcher / Trace`.

Альтернатива — инструменты вызывают чужие Action'ы напрямую из `Infrastructure`. Отклонена: рёбра расползаются по двум десяткам классов, и сократить их потом нельзя.

### D4. Протокол

Классы в `Mcp\Infrastructure\Protocol` — транспортный клей, бизнес-логики в них нет. Путь запроса:

```text
POST /mcp
  -> McpOriginMiddleware        Origin есть и хост != хост APP_URL -> 403
  -> McpTokenMiddleware         401
  -> McpEndpointController     HTTP <-> McpResponse
       -> McpMessageParser      -32700 / -32600, запрос или уведомление
       -> McpHeaderValidator    -32020 (с base64-декодированием Mcp-Name)
       -> McpVersionValidator   -32022 {supported, requested}
       -> McpServer             диспетчер методов: -32601 -> HTTP 404
            server/discover, tools/list, tools/call, prompts/list, prompts/get
```

- `routes/mcp.php`: `POST /mcp`, `GET /mcp` и `DELETE /mcp` → `405`. Подключается в `RouteServiceProvider` без префикса и без групп `api`/`web`: ни троттлинг `api`, ни сессии `web` MCP не нужны.
- Ошибки протокола — исключения `McpProtocolException(code, message, data, httpStatus)`. `McpEndpointController` превращает их в ответ JSON-RPC, всё остальное — в `-32603`.
- HTTP-статусы:
  - `400` — разбор, заголовки, версия;
  - `404` — неизвестный метод;
  - `200` — всё остальное, включая `-32602` на `tools/call`;
  - `202` — уведомление.
- Каждый `result` собирается через `McpResultFactory`: он добавляет `resultType: "complete"` и `_meta["io.modelcontextprotocol/serverInfo"]` (`name`, `title`, `version`), для списков ещё `ttlMs` и `cacheScope: "private"`.
- `McpToolRegistry` хранит инструменты в порядке регистрации в `McpServiceProvider`, поэтому порядок в `tools/list` стабилен. `McpPromptRegistry` в этом изменении пуст.
- Инструкции — `app/Modules/Mcp/Infrastructure/Protocol/instructions.md`, текст на английском. Блок инсталляции с `server_name` и `APP_URL` подставляется перед ним.

### D5. Инструменты

- Интерфейс `McpToolInterface` (`Infrastructure/Tools/Contracts`): `name()`, `title()`, `description()`, `schema(): McpToolSchema`, `call(McpToolArguments $arguments): McpToolResult`.
- Схема аргументов описывается один раз объектами: `McpToolSchema` со списком `McpToolProperty` (имя, тип, описание, обязательность, enum, min/max, items). Из неё `McpToolSchemaCompiler` строит и JSON Schema для `tools/list`, и правила Laravel Validator. Ошибка валидации превращается в `-32602` с именем поля. Массивов-описаний схемы нет.
- `McpToolResult` — данные ответа или ошибка инструмента (`isError`). Поле `installation` и обрезку строк добавляет сервер, а не каждый инструмент.
- Каждый инструмент: аргументы → `Parameters` → мост → компактный ответ.
- `get_trace_data` строит `data` через `TraceDataResource` из модуля `Trace`, чтобы модель и UI видели одно и то же. Это единственное ребро `Infrastructure` → чужой `Infrastructure`, оно тоже вписывается в список.
- `list_incidents` не фильтрует по сервису (см. Context), а отдаёт `service_ids` смотрителя: модель отфильтрует сама. Фильтр по сервису на стороне SLogger потребовал бы менять `FindIncidentsParameters` или ломал бы постраничность.
- Числа событий в `get_incident_events` берутся через `WatcherTypeRegistry::for($type)->eventPayloadMapper()->toDocument($payload)`, так же как в `Notification\Domain\Services\IncidentMessageFactory`.

### D6. Дерево трейса

- Новый `Trace\Domain\Actions\Queries\FindTraceTreeStateAction::handle(string $traceId): ?TraceTreeCacheStateObject`. Он находит корень так же, как `FindTraceTreeAction`, и читает состояние без побочных эффектов.
- `get_trace_tree`:
  - состояния нет — один вызов `FindTraceTreeAction(traceId, fresh: false, isChild: false)` запускает построение, ответ `tree_building`;
  - `InProcess` — `tree_building`;
  - `Failed` или `Canceled` — ошибка инструмента;
  - `Finished` — `FindTraceTreeChildrenAction(rootTraceId, parentTraceId, cursor, limit: tree_nodes_limit)`.
- MCP никогда не читает дерево целиком и не зависит от `module-trace.tree.full_load_limit` (200000).
- `find_in_trace_tree` читает только состояние. Построения не запускает, при `Finished` вызывает `FindTraceTreeFilteredAction`.

### D7. Диапазон данных

Новый `Trace\Domain\Actions\Queries\FindTraceDataRangeAction`. Он берёт первое и последнее имя из `PeriodicTraceService::detectCollectionNames()` и разбирает их в часы тем же форматом `traces_Y_m_d_HH_HH`, что `PeriodicTraceCollectionNameService`. Разбор имени остаётся в `Repositories/Services`, рядом с существующим.

### D8. Конфигурация

`config/mcp.php`:
- `server_name` (`MCP_SERVER_NAME`, по умолчанию `APP_ENV`);
- `server_version` (строка для `serverInfo.version`);
- `max_string_length` (500);
- `tree_nodes_limit` (300);
- `list_ttl_ms` (3600000).

Формат `server_name` проверяет `McpServiceProvider::boot()`: неверное имя — исключение при старте приложения.

## Risks / Trade-offs

- [Токены открытым текстом, видны в дампе БД] → как у `services.api_token`; зато UI показывает токен и команду в любой момент. Выключение и перевыпуск в ЛК.
- [Клиенты на ревизиях до `2026-07-28` не подключатся] → решение принято сознательно: ошибка на `initialize` называет поддерживаемую версию. Claude Code версии 2.1.284 шлёт `server/discover` первым и поддерживает новую ревизию.
- [Изменения PHP не видны по HTTP без перезапуска sconcur-воркера] → при ручной проверке из Claude Code перезапускать воркер и останавливать всё, что было запущено для проверки.
- [`annotations.readOnlyHint` — только подсказка клиенту] → гарантию даёт то, что мосты вызывают только `Queries`-Action'ы, плюс `TraceTreeCacheDeleteRequestedEvent` через `FindTraceTreeAction` — тот же побочный эффект, что у UI.
- [Модель смешивает инсталляции] → блок инсталляции в инструкциях и `installation` в каждом ответе.

## Migration Plan

1. Миграция `create_mcps_table` (имя с UTC-префиксом через `make art c="make:migration create_mcps_table"`).
2. Выкладка: эндпоинт работает сразу, но без подключений любой запрос получает `401`.
3. Откат: удалить или выключить подключения в ЛК; при откате кода — `migrate:rollback` таблицы `mcps`.

