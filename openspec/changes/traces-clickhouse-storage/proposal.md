## Why

Трейсы лежат в MongoDB: почасовые коллекции `traces_*`, view `_traceTreesView` над ними и динамические индексы, которые строятся под каждый набор фильтров. MongoDB держит рабочий набор в кэше WiredTiger (`MONGO_WIRED_TIGER_CACHE_SIZE_GB=10`) и деградирует, когда трейсы в него не помещаются. Поиск по новому набору полей ждёт, пока построится индекс. Трейсы — это append-heavy данные с выборками за период и агрегациями, то есть задача колоночного хранилища. ClickHouse решает её без кэша на весь рабочий набор и без индексов под каждый фильтр.

## What Changes

- Хранилище трейсов — одна таблица `traces` в ClickHouse вместо почасовых коллекций MongoDB. Receiver пишет склеенные трейсы в неё, PHP читает из неё поиск, графики, фасеты, группы, детальную, данные и дерево.
- Переход — замена, без двойной записи и без флага. Трейсы, которые на момент перехода лежат в MongoDB, не переносятся: они живут 3 дня. Путь проверяется стресс-тестом на этом инстансе: генератор `loadgen` шлёт трейсы через сокет receiver тем же протоколом, что клиент.
- В MongoDB остаётся всё, что вокруг трейсов: `buffer`, `invalidBuffer`, `traceMetrics`, `watcherTimelines`, `traceTreeCache`, `traceTreeCacheStates`, `traceAdminStores`, `traceClearingProcesses`, инциденты, события и `notifications`. Добавляется `pendingTraces`: трейсы, ждущие вторую половину (create — свой update, update — свой create), 3 часа.
- Receiver склеивает половины трейсы через `pendingTraces` и пишет в ClickHouse только `INSERT`: трейсы, пришедшие целиком, не читаются вовсе, ClickHouse читается только для update без ожидающей половины. Пачка транспортёра — до 5000 записей (`TRANSPORTER_BATCH_SIZE`). Недоступность хранилища (сеть, таймаут, ClickHouse под нагрузкой) не тратит попытки записей: пачка ждёт и повторяется.
- **BREAKING** Динамические индексы трейсов удаляются. Уходят их построение, реестр `traceDynamicIndexes`, фоновые задачи, WS-события, диалог в UI, эндпоинты `/admin-api/dynamic-indexes`, команда `trace-dynamic-indexes:flush` и ответ `412` «индекс строится». Поиск и графики отвечают сразу.
- **BREAKING** MCP: инструменты `get_trace_index_status` и `get_trace_indexes` удаляются. Статуса `index_building` и ошибок `index_error` и `tags_with_data_filter` больше нет: теги и фильтр по `data` можно задавать вместе.
- **BREAKING** MCP: период берётся точно по `from`/`to`, без выравнивания по часам, и ограничен сроком хранения трейсов вместо 24 часов. `aggregate_traces` принимает `data_filter`. Инструкции сервера переписываются.
- **BREAKING** Срок хранения задаётся в часах: `TRACES_LIFETIME_HOURS` (по умолчанию 72) вместо `TRACES_LIFETIME_DAYS`. `ClearTracesJob` удаляет часовые партиции таблицы старше срока вместо почасовых коллекций. Страница Trace cleaner и её API не меняются.
- Статистика хранилища на Dashboard показывает таблицу трейсов в ClickHouse рядом с коллекциями MongoDB.
- Раз в час `OptimizeTracesJob` сливает каждый час, закрытый больше часа назад, в одну часть (`OPTIMIZE … PARTITION … FINAL`): у трейсы, записанной в два шага, остаётся одна строка.
- Инфраструктура: сервис `clickhouse` в docker-compose, настройки под ограниченную память, переменные окружения PHP и receiver, цели makefile. Таблица ClickHouse и коллекция `pendingTraces` создаются обычными Laravel-миграциями; `migrate:fresh` удаляет и таблицы ClickHouse и принимает `--force`.
- Генератор нагрузки `servers/receiver/cmd/loadgen` для стресс-тестов; результаты — в `.ai/plans/clickhouse-traces-prototype.md`.

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
  - `Cleaner`, слои `Domain`/`Infrastructure`: очистка идёт через новое удаление партиций в `Trace`, ежечасное слияние закрытых часов — `OptimizeTracesAction` и `OptimizeTracesJob`;
  - `Dashboard`, слой `Repositories`: статистика ClickHouse;
  - `Mcp`, слои `Domain`/`Infrastructure`: удаляются инструменты и переводчик исключений индексов;
  - `app/Services`: новый клиент ClickHouse поверх `SConcur\Features\HttpClient\HttpClient`.
- Receiver (`servers/receiver`): запись трейсов в ClickHouse вместо почасовых коллекций, склейка через `pendingTraces`, удаляются `trace_sharding_service` и view; новый бинарник `cmd/loadgen`.
- `app/Console/Commands/Migrate`: `migrate:fresh` удаляет таблицы ClickHouse; `database/migrations`: таблица `traces` и коллекция `pendingTraces`.
- Admin API: удаляется `/admin-api/dynamic-indexes`, ответ `412` у поиска, графиков и фасетов. Нужны `make oa-generate` и `make frontend-npm-build`.
- Frontend: удаляются диалог динамических индексов и ожидание индекса в запросах.
- Зависимости: образ `clickhouse/clickhouse-server` (LTS). Новых composer- и Go-пакетов нет, всё идёт по HTTP.
- Документация: `README.md` и `README.ru.md`, раздел MCP в `.ai/README.md`, `resources/mcp/instructions.md`.
