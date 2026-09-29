## Why

Трейсы лежат в MongoDB: почасовые коллекции `traces_*`, view `_traceTreesView` над ними и динамические индексы, которые строятся под каждый набор фильтров. MongoDB держит рабочий набор в кэше WiredTiger (`MONGO_WIRED_TIGER_CACHE_SIZE_GB=10`) и деградирует, когда трейсы в него не помещаются. Поиск по новому набору полей ждёт, пока построится индекс. Трейсы — это append-heavy данные с выборками за период и агрегациями, то есть задача колоночного хранилища. ClickHouse решает её без кэша на весь рабочий набор и без индексов под каждый фильтр.

## What Changes

- Хранилище трейсов — одна таблица `traces` в ClickHouse вместо почасовых коллекций MongoDB. Receiver пишет склеенные трейсы в неё, PHP читает из неё поиск, графики, фасеты, группы, детальную, данные и дерево.
- Переход — замена, без двойной записи и без флага. Трейсы, которые на момент перехода лежат в MongoDB, не переносятся: они живут 3 дня, и сравнение идёт со вторым инстансом SLogger на этой машине.
- В MongoDB остаётся всё, что вокруг трейсов: `buffer`, `invalidBuffer`, `traceMetrics`, `watcherTimelines`, `traceTreeCache`, `traceTreeCacheStates`, `traceAdminStores`, `traceClearingProcesses`, инциденты, события и `notifications`.
- **BREAKING** Динамические индексы трейсов удаляются. Уходят их построение, реестр `traceDynamicIndexes`, фоновые задачи, WS-события, диалог в UI, эндпоинты `/admin-api/dynamic-indexes`, команда `trace-dynamic-indexes:flush` и ответ `412` «индекс строится». Поиск и графики отвечают сразу.
- **BREAKING** MCP: инструменты `get_trace_index_status` и `get_trace_indexes` удаляются. Статуса `index_building` и ошибок `index_error` и `tags_with_data_filter` больше нет: теги и фильтр по `data` можно задавать вместе.
- **BREAKING** MCP: период берётся точно по `from`/`to`, без выравнивания по часам, и ограничен сроком хранения трейсов вместо 24 часов. `aggregate_traces` принимает `data_filter`. Инструкции сервера переписываются.
- **BREAKING** Срок хранения задаётся в часах: `TRACES_LIFETIME_HOURS` (по умолчанию 72) вместо `TRACES_LIFETIME_DAYS`. `ClearTracesJob` удаляет часовые партиции таблицы старше срока вместо почасовых коллекций. Страница Trace cleaner и её API не меняются.
- Статистика хранилища на Dashboard показывает таблицу трейсов в ClickHouse рядом с коллекциями MongoDB.
- Инфраструктура: сервис `clickhouse` в docker-compose, настройки под ограниченную память, миграции ClickHouse, переменные окружения PHP и receiver, цели makefile.

## Capabilities

### New Capabilities
- `trace-storage`: хранение трейсов — склейка create и update, срок хранения, выборка за период, фильтр по `data`, поиск без ожидания индексов.

### Modified Capabilities
- `mcp-trace-queries`: удаляются статус построения индекса и инструменты индексов, снимается запрет на `tags` вместе с `data_filter`, правило периода становится точным и ограниченным сроком хранения, `aggregate_traces` получает `data_filter`.
- `mcp-tools`: из допустимых побочных эффектов общего формата ответа удаляется построение динамических индексов.

## Impact

- Модули `app/Modules`:
  - `Trace`, слой `Repositories`: репозитории трейсов переписываются на ClickHouse, `PeriodicTraceService` и сервисы коллекций удаляются;
  - `Trace`, слой `Domain`: удаляется подсистема динамических индексов, `DeleteCollectionsAction` удаляет партиции вместо коллекций;
  - `Trace`, слой `Infrastructure`: удаляются контроллер, таски, команды и broadcast динамических индексов, меняется провайдер;
  - `Cleaner`, слой `Domain`: очистка идёт через новое удаление партиций в `Trace`;
  - `Dashboard`, слой `Repositories`: статистика ClickHouse;
  - `Mcp`, слои `Domain`/`Infrastructure`: удаляются инструменты и переводчик исключений индексов;
  - `app/Services`: новый клиент ClickHouse поверх `SConcur\Features\HttpClient\HttpClient`.
- Receiver (`servers/receiver`): запись трейсов в ClickHouse вместо почасовых коллекций, удаляются `trace_sharding_service` и view.
- Admin API: удаляется `/admin-api/dynamic-indexes`, ответ `412` у поиска, графиков и фасетов. Нужны `make oa-generate` и `make frontend-npm-build`.
- Frontend: удаляются диалог динамических индексов и ожидание индекса в запросах.
- Зависимости: образ `clickhouse/clickhouse-server` (LTS). Новых composer- и Go-пакетов нет, всё идёт по HTTP.
- Документация: `README.md` и `README.ru.md`, раздел MCP в `.ai/README.md`, `resources/mcp/instructions.md`.
