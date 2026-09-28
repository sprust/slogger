## 1. Подключения: хранение и домен

- [x] 1.1 Миграция `create_mcps_table` через `make art c="make:migration create_mcps_table"` (колонки из design D2, таймстемпы последними); проверка: `make art c="migrate"` создаёт таблицу
- [x] 1.2 Модель `App\Models\Mcps\Mcp`, фабрика `database/factories/Mcps/McpFactory.php`; проверка: `make art c="migrate"` проходит, модель читается репозиторием
- [x] 1.3 `McpRepository` с примитивами из D2 (возвращает `McpObject`), `Mcp\Entities\McpObject`, `Mcp\Parameters` для создания и изменения; проверка: тесты Action'ов на замоканном репозитории (тесты проекта в БД не ходят)
- [x] 1.4 Action'ы `Mutations` (`CreateMcpAction`, `UpdateMcpAction`, `RegenerateMcpTokenAction`, `DeleteMcpAction`, `TouchMcpAction`) и `Queries` (`FindMcpsAction`, `FindMcpAction`, `FindMcpByTokenAction`); проверка: тесты — токен 50 символов и уникален, перевыпуск меняет токен, `FindMcpByTokenAction` не находит выключенное, `TouchMcpAction` не пишет чаще раза в минуту
- [x] 1.5 `Mcp\Infrastructure\McpServiceProvider` (контракты по образцу `WatcherServiceProvider`), регистрация в `ModulesConfig::getProviders()`; проверка: `make code-analise-deptrac` без нарушений

## 2. Подключения: admin API

- [x] 2.1 `config/mcp.php` из D8, `MCP_SERVER_NAME` и лимиты в `.env.example`; проверка формата `server_name` в `McpServiceProvider::boot()`; проверка: тест — неверное имя даёт исключение, по умолчанию берётся `APP_ENV`
- [x] 2.2 Запросы (`name`: `required|string|min:1|max:255`, `enabled`: `boolean`), ресурс подключения, `McpController` (`index`, `show`, `create`, `update`, `regenerateToken`, `delete`), `McpSettingsController`; маршруты `/mcps` в `routes/admin-api.php` (`settings` до `{id}`, `{id}` через `whereNumber`); проверка: HTTP-тесты в `tests/Modules/Mcp/Infrastructure/Http` на все сценарии admin API из `specs/mcp-connections`, включая `401` без сессии и `404`
- [x] 2.3 `make oa-generate`; проверка: схема в `storage/api` и `frontend/src/api-schema` содержит `/mcps` и `/mcps/settings`

## 3. Протокол

- [x] 3.1 `Mcp\Infrastructure\Protocol`: `McpMessageParser`, `McpHeaderValidator` (с base64-декодированием), `McpVersionValidator`, `McpProtocolException`, `McpResultFactory`; проверка: юнит-тесты на `-32700`, `-32600` (batch и не JSON-RPC), `-32020` (нет заголовка, несовпадение, нет версии в `_meta`, base64), `-32022` с `supported`/`requested`
- [x] 3.2 `McpToolInterface`, `McpToolSchema`/`McpToolProperty`, `McpToolSchemaCompiler` (JSON Schema и правила валидатора из одного описания), `McpToolArguments`, `McpToolResult`, `McpToolRegistry`, `McpPromptRegistry`; проверка: юнит-тест — JSON Schema компилятора валидна, неверный аргумент даёт `-32602` с именем поля
- [x] 3.3 `McpServer`: `server/discover`, `tools/list`, `tools/call`, `prompts/list`, `prompts/get`, неизвестный метод — `-32601`; поле `installation` и обрезка строк до `max_string_length` для ответов инструментов, кроме `get_trace_data`; `resources/mcp/instructions.md` на английском с блоком инсталляции; проверка: юнит-тесты на `resultType`, `serverInfo`, `ttlMs`/`cacheScope`, стабильный порядок инструментов, `isError` для ошибок инструмента
- [x] 3.4 `McpOriginMiddleware`, `McpTokenMiddleware` (`401`, `McpObject` в атрибутах, `TouchMcpAction` в `terminate`), `McpEndpointController`, `routes/mcp.php` (`POST`, `GET`/`DELETE` → `405`), подключение в `RouteServiceProvider`; проверка: HTTP-тесты на все сценарии `specs/mcp-protocol` — `403`, `401` (нет, неизвестный, выключенный, удалённый, перевыпущенный токен), `202` на уведомление, `404` на `initialize` и `resources/list`, `-32020` на `initialize` без заголовка, игнор `Mcp-Session-Id`, обновление `last_used_at`

## 4. Мосты и инструменты: сервисы и инциденты

- [x] 4.1 `Trace\Domain\Actions\Queries\FindTraceDataRangeAction` с разбором имени коллекции в `Trace\Repositories\Services`; проверка: тест — первый и последний час по списку коллекций, `null` без коллекций
- [x] 4.2 Мосты `FindMcpServicesAction`, `FindMcpDataRangeAction`, `FindMcpIncidentsAction`, `FindMcpIncidentEventsAction` и инструменты `list_services`, `get_data_range`, `list_incidents`, `get_incident_events`; проверка: тесты на каждый инструмент по сценариям `specs/mcp-tools` (поиск без учёта регистра, `has_more`, `service_ids` смотрителя, числа события через `eventPayloadMapper`, ошибка инструмента на неизвестный инцидент)
- [x] 4.3 Рёбра `Mcp → Service`, `Mcp → Watcher`, `Mcp → Trace` для мостов этой группы в `.ai/README.md` → Cross-Module Dependencies; `Mcp` в Module Responsibilities; проверка: список совпадает с `use` в `Mcp\Domain\Actions\Bridges`

## 5. Мосты и инструменты: трейсы

- [x] 5.1 `Trace\Domain\Actions\Queries\FindTraceTreeStateAction` (корень как в `FindTraceTreeAction`, без побочных эффектов); проверка: тест — состояние читается, событие `TraceTreeCacheDeleteRequestedEvent` не отправляется
- [x] 5.2 Мост `FindMcpTraceAction`, инструменты `get_trace` и `get_trace_data` (`data` через `TraceDataResource`); проверка: тесты — `get_trace` без `data`, `data` из `get_trace_data` совпадает с `data` детали трейса в admin API, ошибка на неизвестный `trace_id`
- [x] 5.3 Мосты `FindMcpTraceTreeAction`, `FindMcpTraceTreeFilteredAction`, инструменты `get_trace_tree` и `find_in_trace_tree` по D6; проверка: тесты — первый вызов запускает построение и отдаёт `tree_building`, `InProcess` — `tree_building`, `Failed`/`Canceled` — ошибка без нового построения, `Finished` — узлы с `next_cursor` по `tree_nodes_limit`, `find_in_trace_tree` без фильтров — `-32602`, на непостроенном дереве — `tree_building` без запуска построения
- [x] 5.4 Дописать в `.ai/README.md` рёбра мостов этой группы и ребро `Mcp\Infrastructure → Trace\Infrastructure\Http\Resources\Data\TraceDataResource`; раздел про модуль `Mcp` (мосты, `Infrastructure/Protocol`, `Infrastructure/Tools`); проверка: список совпадает с `use` в модуле `Mcp`

## 6. Фронтенд

- [x] 6.1 Маршрут `mcps` в `frontend/src/utils/router.ts`, пункт в `Header.vue`, имя инсталляции из `/mcps/settings` в шапке с заранее зарезервированным местом; проверка: `make frontend-npm-build` проходит, шапка не прыгает при загрузке имени
- [x] 6.2 Страница `frontend/src/components/pages/mcps` по образцу `ChannelList.vue`/`ChannelFormDialog.vue`: таблица, переключатель `enabled`, создание, переименование, удаление и перевыпуск с подтверждением, токен, команда `claude mcp add` и вариант `.mcp.json` с копированием; без ручных `font-size`; проверка: `make frontend-npm-build` проходит, сценарии страницы из `specs/mcp-connections` проверены в headless Chrome (создание, раскрытие с токеном и командами, переключатель, переименование, перевыпуск и удаление с подтверждением, имя в шапке)

## 7. Документация

- [x] 7.1 Раздел «MCP» в `README.md` и `README.ru.md` одновременно: подключение командой со страницы `/mcps`, несколько инсталляций, `.mcp.json` с токеном из переменной окружения, выключение и перевыпуск; флаги `claude mcp add` и подстановку `${VAR}` сверить с документацией Claude Code; проверка: команда из README подключает сервер в Claude Code

## 8. Проверка

- [x] 8.1 `make check` проходит (PHPStan, Deptrac, CS Fixer, тесты)
- [x] 8.2 `make oa-generate` не даёт расхождений со сгенерированной схемой
- [x] 8.3 `make frontend-npm-build` проходит
- [x] 8.4 Живая проверка: перезапустить sconcur-воркер, создать подключение на `/mcps`, подключить Claude Code командой со страницы, убедиться, что `/mcp` показывает сервер `slogger-<server_name>` в статусе connected, инструкции получены, все инструменты этого изменения вызываются; после проверки остановить всё запущенное
