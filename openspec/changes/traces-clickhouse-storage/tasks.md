## 1. Инфраструктура ClickHouse

- [x] 1.1 Добавить сервис `clickhouse` в `docker-compose.yml`: LTS-образ `clickhouse/clickhouse-server` не ниже 25.8 с зафиксированным тегом, том, `ulimits nofile`, `mem_limit: ${CLICKHOUSE_MEM_LIMIT}`, HTTP-порт только внутри сети, `depends_on` у `php-fpm`, `workers` и `receiver`. Проверка: `make up` поднимает контейнер, `SELECT version()` через `clickhouse-client` отвечает.
- [x] 1.2 Добавить `docker/clickhouse/config.d/low-memory.xml` (`max_server_memory_usage`, `mark_cache_size`, выключенные системные логи, кроме `query_log` с TTL 3 дня) и `docker/clickhouse/users.d/profile.xml` (`max_memory_usage`, внешние сортировка и группировка, `max_threads`, `do_not_merge_across_partitions_select_final = 1`). Проверка: `SELECT name, value FROM system.settings WHERE changed` показывает значения профиля.
- [x] 1.3 Добавить переменные в `.env.example` (`CLICKHOUSE_HOST`, `CLICKHOUSE_PORT`, `CLICKHOUSE_DATABASE`, `CLICKHOUSE_USERNAME`, `CLICKHOUSE_PASSWORD`, `CLICKHOUSE_MEM_LIMIT`) и в `servers/receiver/.env.example` (`CLICKHOUSE_URL`, `CLICKHOUSE_DATABASE`, `CLICKHOUSE_USERNAME`, `CLICKHOUSE_PASSWORD`); секцию `clickhouse` в `config/database.php`. Проверка: `make art c="tinker --execute=\"dump(config('database.connections.clickhouse'))\""` показывает настройки.
- [x] 1.4 Добавить `make clickhouse-client`. Проверка: команда открывает клиент в контейнере.

## 2. PHP-клиент и миграции ClickHouse

- [x] 2.1 Создать `app/Services/Clickhouse`: `ClickhouseClient` поверх `SConcur\Features\HttpClient\HttpClient` (`select` с построчным разбором `JSONEachRow`, `command`), `ClickhouseConnectionConfig`, `ClickhouseQueryException` с кодом из `X-ClickHouse-Exception-Code`. Значения передаются только через `param_<name>`, каждый запрос получает `query_id` с префиксом сценария. Проверка: юнит-тесты на сборку URL и параметров, разбор ответа и ошибки на фейковом `ClientInterface`.
- [x] 2.2 Команда `clickhouse:migrate` в `app/Console/Commands/Migrate`: таблица `schema_migrations`, файлы `database/clickhouse/*.sql` по порядку, один файл — одна команда, повторный запуск ничего не делает. Проверка: два запуска подряд — второй сообщает, что применять нечего. Заменено задачей 12.6.
- [x] 2.3 Миграция `database/clickhouse/001_traces.sql` с таблицей из design (`ReplacingMergeTree(uat)`, `PARTITION BY toStartOfHour(lat)`, `ORDER BY (sid, lat, tid)`, skip-индексы). Проверка: `SHOW CREATE TABLE traces` совпадает с design. Таблица создаётся Laravel-миграцией, `dt` — по 12.4 (задачи 12.4, 12.6).
- [x] 2.4 Добавить `clickhouse:migrate` в `make setup` и `make deploy-prod` рядом с `migrate`. Проверка: `make setup` на чистом окружении создаёт таблицу. Заменено задачей 12.6: таблицу создаёт `migrate`.

## 3. Receiver: запись в ClickHouse

- [x] 3.1 Хелпер `internal/helpers/json_helper`: сериализатор `bson.D`/`bson.A` → JSON с сохранением порядка ключей. Проверка: `go test` на порядок ключей, вложенные массивы, `float64`, `null`, пустой объект и не-объектный корень.
- [x] 3.2 Пакет `internal/repositories/clickhouse_trace_repository`: `FindExisting(ctx, keys)` — `SELECT … FROM traces FINAL WHERE (sid, lat, tid) IN (…)`, и `Insert(ctx, rows)` — `INSERT … FORMAT JSONEachRow` по HTTP с basic auth, gzip и таймаутом. Строка содержит `sid, tid, ptid ('' вместо nil), tp, st, tgs, dt ({} для не-объекта), dt_raw, dur, mem, cpu, lat, cat, uat`. Проверка: `go test` на сборку строки и разбор ответа; интеграционный тест с тегом `integration` против локального ClickHouse — вставка, повторная вставка той же трейсы и чтение `FINAL` дают одну строку.
- [x] 3.3 Переписать `periodic_trace_service.Save` на пачку: один `FindExisting` на проход, склейка по текущим правилам из найденной строки (`dt_raw` вместо `bson` документа), `cat` из найденной строки или текущее время, одна вставка на проход. Семафор на 64 сохранения удалить. `countsAsNew` и передача длительности в watchers остаются. Проверка: существующие тесты склейки проходят, новые — «update раньше create», «повторный create», «create без update». Поиск сохранённой половины заменён задачей 12.1.
- [x] 3.4 В `traces_transporter` ошибка `FindExisting` или `Insert` помечает всю пачку через `MarkFailed`, успех удаляет её из `buffer`. Проверка: `go test` транспортёра с фейковым репозиторием на оба исхода. Недоступность хранилища обрабатывается по 12.3.
- [x] 3.5 Удалить `trace_sharding_service`, создание почасовых коллекций, их индексов и view `_traceTreesView`, переменную `MONGODB_DB_PERIODIC_TRACES`; убрать `tss`, `hpr`, `pr`, `rcLat`, `ucLat` из записи. Проверка: `go build ./...`, `go vet ./...`, `go test ./...` в `servers/receiver`; `grep` не находит `tracesPeriodic` и `trace_sharding_service`.

## 4. PHP: репозитории трейсов на ClickHouse

- [x] 4.1 `Trace/Repositories/Services/ClickhouseTraceFilterBuilder`: `WHERE` и параметры для сервисов, `tid`, периода, типов, статусов, тегов (`hasAll`), диапазонов `dur`/`mem`/`cpu` и фильтра по `data` по таблице из design. Путь проверяется по белому списку, строка сравнивается как текст. Проверка: юнит-тесты на каждое условие, путь через массив, отказ на недопустимом пути и отсутствие значений в тексте SQL.
- [x] 4.2 `ClickhouseDataPathTypes`: `distinctJSONPathsAndTypes(dt)` за последние сутки раз в 5 минут, кэш в Redis; построитель по нему строит `arrayExists` для путей через массив. Проверка: юнит-тест построителя с путём, который есть и как объект, и как массив, даёт `OR` обоих вариантов.
- [x] 4.3 `TraceRepository` на ClickHouse: `find` (страница, `ORDER BY lat DESC, tid`, поля `data` в выборке), `findOneDetailByTraceId` (`dt_raw` → `TraceDataToObjectBuilder`), `findTreeNodesByTraceIds`, `findProfilingByTraceId` возвращает `null`. `findTraceIds` и `FindTraceIdsAction` удалены: их никто не вызывал. Методы индексов удалить. Проверка: переписанный `TraceRepositoryTest` на фейковом клиенте проверяет SQL, параметры и маппинг в DTO.
- [x] 4.4 `TraceTimestampsRepository`: `toStartOfInterval` по шагу (`toStartOfMonth` для `m`), `count`, `avg`, `min`, `max`, `quantiles(0.5, 0.95, 0.99)` по `dur`/`mem`/`cpu` и числовым полям `data`. Удалить `TraceTimestampCollectionBatcher` и `TraceMetricAggregationFactory` вместе с их тестами. Проверка: тест репозитория на каждый шаг и метрики; график на странице трейсов строится.
- [x] 4.5 `TraceContentRepository`: типы, теги, статусы одним запросом каждый, поиск без учёта регистра, лимит как сейчас. Проверка: тест репозитория; фасеты на странице трейсов совпадают по составу с поиском.
- [x] 4.6 `TraceGroupsRepository`: группы и сравнение групп на SQL из design, `data_filter` в группах. Проверка: переписанный `TraceGroupsRepositoryTest`.
- [x] 4.7 `TraceTreeRepository`: родитель, цепочка до корня, дети по `ptid` пачками по 1000 с параллельностью как сейчас. `BuildTraceTreeCacheJob` строит кэш в MongoDB без изменений. Проверка: тест репозитория; дерево трейсы с детьми строится и открывается в UI.
- [x] 4.8 `FindTraceDataRangeAction` читает `min(lat)`/`max(lat)` из ClickHouse вместо имён коллекций. Проверка: тест action; `get_trace_time_range` возвращает часы, за которые есть трейсы.
- [x] 4.9 Удалить `PeriodicTraceService`, `PeriodicTraceCollectionNameService`, `TracePipelineBuilder`, модель `TraceTree`, соединение `mongodb.tracesPeriodic` из `config/database.php` и привязку в `TraceServiceProvider`, вместе с их тестами. Проверка: `grep` не находит эти классы; `make check` проходит.

## 5. Удаление динамических индексов

- [x] 5.1 Удалить из `Trace` инициализатор, исключения, события, сущности, DTO, репозиторий, модель `TraceDynamicIndex`, actions построения, удаления, flush и поиска индексов, таски, команды, broadcast'ы, listener, контроллер и ресурсы. Убрать вызовы инициализатора из `FindTracesAction`, `FindTraceIdsAction`, `FindTraceTimestampsAction`, `FindTypesAction`, `FindTagsAction`, `FindStatusesAction`, `FindTraceGroupsAction`, `CompareTraceGroupsAction`. Проверка: `grep -ri dynamicindex app config routes` пуст; `make check` проходит.
- [x] 5.2 Убрать задачи индексов из `config/sconcur.php` и `config/slogger.php`, слушатель из `EventServiceProvider`, маршруты `/admin-api/dynamic-indexes` из `routes/admin-api.php`, обработку `412` и «parallel arrays» из `app/Exceptions/Handler.php`. Проверка: `make art c="route:list"` не показывает `dynamic-indexes`; пул `tasks` стартует без них.
- [x] 5.3 Laravel-миграция MongoDB (имя с UTC-меткой из `date -u +%Y_%m_%d_%H%M%S`), удаляющая коллекцию `traceDynamicIndexes`. Проверка: `make art c="migrate"` удаляет коллекцию, повторный запуск ничего не делает. Заменено задачей 12.16: миграции создания и удаления коллекции удалены.
- [x] 5.4 Удалить тесты удалённого кода (`TraceDynamicIndexInitializerTest`, тесты actions индексов, broadcast'ов и `PublishTraceDynamicIndexStatsTaskTest`) и поправить `FindTracesActionTest` и `TraceGroupsActionsTest` без инициализатора. Проверка: `make test` проходит.

## 6. Admin API и фронтенд

- [x] 6.1 Выполнить `make oa-generate`. Проверка: из `storage/api/json-schemes/admin-api-openapi-scheme.json` и `frontend/src/api-schema` пропали `dynamic-indexes` и ответ `412`.
- [x] 6.2 Удалить `frontend/src/components/pages/trace-aggregator/components/dynamic-indexes`, ожидание индекса в `PendingRequestDialog.vue`, `pendingRequestStore.ts` и `handleApiRequest.ts`, подписки на события индексов в `echoContainer.ts` и упоминания в `App.vue`, `TraceAggregatorTraces.vue`, `TraceAggregatorGraph.vue`, `authStore.ts`. Проверка: `make frontend-npm-build` проходит; поиск и график на странице трейсов отвечают без диалога ожидания.

## 7. MCP

- [x] 7.1 Удалить `GetTraceIndexStatusTool`, `GetTraceIndexesTool`, бриджи `FindMcpIndexStatusAction` и `FindMcpDynamicIndexesAction`, `McpTraceIndexExceptionTranslator`, `McpTraceIndexBuildingException`, `McpTraceIndexFailedException`, `McpIndexStatusObject`, `McpIndexStatusEnum`, `McpToolFormatter::indexBuilding` и регистрацию в `McpServiceProvider::TOOLS`. Проверка: `tools/list` не содержит двух инструментов; `IndexToolsTest` и `FindMcpIndexStatusActionTest` удалены, остальные тесты MCP проходят.
- [x] 7.2 Снять ошибку `tags_with_data_filter` в `SearchTracesTool`; `tags` и `data_filter` передаются вместе. Проверка: тест инструмента «Теги и фильтр по data» из спеки.
- [x] 7.3 `McpTracePeriodResolver` берёт границы точно, отказывает на `from >= to` и на периоде длиннее `TRACES_LIFETIME_HOURS` с указанием срока в тексте `period_too_wide`. Проверка: тесты на сценарии «Выравнивание», «Весь срок хранения», «Слишком длинный период», «Пустой период».
- [x] 7.4 `aggregate_traces` принимает `data_filter` через `McpTraceDataFilterParser`; `FindMcpTraceGroupsAction` передаёт его в `TraceFindGroupsParameters`. Проверка: тест инструмента «Группы с фильтром по data» и ошибка `invalid_data_filter`.
- [x] 7.5 Переписать `resources/mcp/instructions.md` и описания инструментов в их `McpToolSchema`: без индексов и `index_building`, период — точные границы не длиннее срока хранения, `aggregate_traces` с `data_filter`. Проверка: `grep -i index resources/mcp/instructions.md` находит только индексы логов; тест инструкций сервера проходит.

## 8. Cleaner и срок хранения

- [x] 8.1 `TRACES_LIFETIME_HOURS` (по умолчанию 72) вместо `TRACES_LIFETIME_DAYS` в `config/cleaner.php` и `.env.example`. Проверка: `grep -r TRACES_LIFETIME_DAYS` пуст вне `openspec/`.
- [x] 8.2 `DeleteCollectionsAction` и репозиторий удаляют часовые партиции старше срока (`ALTER TABLE traces DROP PARTITION`), число строк берут из `system.parts` перед удалением; `ClearTracesAction` записывает число партиций в `clearedCollectionsCount`. Проверка: тест action на фейковом клиенте; ручной запуск `make art c=traces-clearing:clear` показывает запуск на странице Trace cleaner.

## 9. Dashboard

- [x] 9.1 `DatabaseStatRepository` добавляет базу `clickhouse` с таблицей `traces`: размер и строки из `system.parts`, skip-индексы из `system.data_skipping_indices`, память из `system.metrics`. Соединение `mongodb.tracesPeriodic` из обхода пропадает вместе с конфигом. Проверка: тест репозитория на фейковом клиенте; вкладка Storage показывает `clickhouse`.

## 10. Документация

- [x] 10.1 `README.md` и `README.ru.md` вместе: схема данных, хранение в ClickHouse вместо шардирования, удаление раздела о динамических индексах, фильтр по `data` (теги вместе с `data`, текстовое сравнение), очистка по `TRACES_LIFETIME_HOURS`, MCP (без инструментов индексов, период), переменные окружения, стек. Проверка: `grep -i "dynamic ind\|traces_YYYY\|TRACES_LIFETIME_DAYS"` в обоих файлах пуст, разделы совпадают по структуре.
- [x] 10.2 `.ai/README.md`: раздел MCP Server и межмодульные связи `Mcp` → `Trace` без бриджей и переводчика индексов, упоминание ClickHouse в обзоре и хранилищах. Проверка: каждый упомянутый класс существует в коде.

## 11. Проверка

- [x] 11.1 `make check` (PHPStan, Deptrac, CS Fixer, PHPUnit) проходит.
- [x] 11.2 `make oa-generate` не даёт новых изменений схемы после всех правок.
- [x] 11.3 `make frontend-npm-build` проходит.
- [x] 11.4 В `servers/receiver`: `go build ./...`, `go vet ./...`, `go test ./...` и интеграционные тесты с тегом `integration` против локального ClickHouse проходят.
- [x] 11.5 Сквозная проверка на этом инстансе: отправить трейсы через сокет receiver (create и update, update раньше create, дерево с детьми, `data` с массивом объектов). В панели поиск, график, фасеты, детальная и дерево показывают их; `SELECT count(), uniqExact(sid, tid) FROM traces FINAL` совпадают; `buffer` пустеет.
- [x] 11.6 ~~Сравнение со вторым инстансом~~ заменено стресс-тестом на этом инстансе (раздел 12): 12 млн трейс через receiver, отказ ClickHouse посреди приёма, дерево в 200 тыс. узлов, время чтения. Результат — `.ai/plans/clickhouse-traces-prototype.md`, раздел «Результаты».
- [x] 11.7 Остановить процессы и контейнеры, запущенные только для проверки.

## 12. Стресс-тест и доработки по его итогам

- [x] 12.1 Receiver: вторая половина трейсы ищется в MongoDB `pendingTraces` (`internal/repositories/pending_trace_repository`, `FindMany` по `_id` `"sid:tid"`, `Apply` одним `BulkWrite`), ClickHouse читается только для update без ожидающей половины. Финальная трейса вставляется, create со `started` вставляется и ждёт, update без create только ждёт. Проверка: тесты `periodic_trace_service` на каждый исход и на отказ каждого хранилища; прогон 1 млн — ни одного `SELECT FINAL` на трейсы, пришедшие целиком.
- [x] 12.2 Пачка транспортёра — `TRANSPORTER_BATCH_SIZE` (по умолчанию 5000) в `buffer_service`, переменная в `servers/receiver/.env.example`. Проверка: `go test ./...`.
- [x] 12.3 Недоступность хранилища (сеть, таймаут, коды 159, 202, 209, 210, 241, 242, 252) не тратит попытки: пауза от 1 до 30 секунд и повтор пачки как есть. Проверка: тест `isUnavailable`; перезапуск ClickHouse во время приёма 300 тыс. трейс — все в хранилище, `invalidBuffer` пуст.
- [x] 12.4 `dt JSON(max_dynamic_paths = 256, SKIP REGEXP '^cache\\.')`. Проверка: в части нет файлов `dt.cache.*`; пик памяти слияний ниже 2 ГБ на прогоне 12 млн.
- [x] 12.5 Конфигурация ClickHouse: `max_server_memory_usage_to_ram_ratio` 0.85 от `CLICKHOUSE_MEM_LIMIT` (было `max_server_memory_usage` 5 ГБ в XML), `background_pool_size` 4 и пороги `merge_tree` в свободных слотах пула, `log_queries_cut_to_length` 10000. Проверка: `system.server_settings` и `system.settings` показывают значения, сервер стартует.
- [x] 12.6 Миграции ClickHouse — Laravel-миграция `2026_09_29_192649_clickhouse_create_traces_table` через `ClickhouseClient`; удалены `clickhouse:migrate`, `database/clickhouse` и `schema_migrations`, цели makefile; `migrate:fresh` удаляет таблицы ClickHouse. Проверка: `make art c="migrate:fresh --force"` пересоздаёт `traces`.
- [x] 12.7 Коллекция `pendingTraces` и её TTL-индекс `uat_1` (3 часа) — Laravel-миграция `2026_09_29_194325_mongodb_create_pending_traces_collection`; receiver индекс не создаёт. Проверка: после `migrate:fresh` индекс есть, повторный `migrate` его не трогает.
- [x] 12.8 `migrate:fresh` объявляет `--force`. Проверка: `make art c="help migrate:fresh"` показывает опцию.
- [x] 12.9 Пакетный lightweight `DELETE` вытесненной половины убран: на потоке 12 тыс. трейс в секунду мутации заняли весь пул и остановили слияния. Проверка: прогон 12 млн — частей не больше 14 на партицию, мутаций нет.
- [x] 12.10 `OptimizeTracesJob` в 10 минут каждого часа: `Cleaner\OptimizeTracesAction` → `Trace\OptimizePartitionsAction` → `TraceRepository::optimizePartitions` (`OPTIMIZE … PARTITION ID … FINAL` для часов, закрытых больше часа назад и лежащих в нескольких частях). Связь `Cleaner` → `Trace` — в `.ai/README.md`. Проверка: тесты `optimizePartitions`; запуск под потоком — 24 партиции за 179 секунд без ошибок, в слитом часе одна строка на трейсу.
- [x] 12.11 Генератор нагрузки `servers/receiver/cmd/loadgen`. Проверка: 12 019 381 трейс отправлено, `uniqExact(sid, tid)` совпадает.
- [x] 12.12 README (оба языка): склейка через `pendingTraces`, пачка и повторы транспортёра, миграции, ежечасное слияние. `.ai/plans/clickhouse-traces-prototype.md`: результаты стресс-теста.
- [x] 12.13 `make check` проходит; в `servers/receiver` — `go vet ./...`, `go test ./...`.
- [ ] 12.14 Код 60 (`UNKNOWN_TABLE`) считать недоступностью хранилища: во время `migrate:fresh` собственные трейсы панели уходили в `invalidBuffer`.
- [x] 12.15 Карту путей `ClickhouseDataPathTypes` строить в фоне, а не в запросе пользователя: `RefreshTraceDataPathTypesTask` в пуле `tasks` при старте и раз в 5 минут, кэш 30 минут, `arrayPaths()` только читает. Проверка: `ClickhouseDataPathTypesTest`, `RefreshTraceDataPathTypesTaskTest`; после перезапуска воркеров карта появляется в кэше без запросов.
- [x] 12.16 Миграции сжаты: одна миграция создания на таблицу или коллекцию в текущем состоянии, под прежним именем файла; миграции изменений и удалённых фич (`trace_clearing_settings`, `traceDynamicIndexes`, `logs`) удалены. Проверка: снимок схемы MySQL, MongoDB (опции, валидаторы, индексы) и ClickHouse после `migrate:fresh --force` совпадает со снимком до сжатия.
- [x] 12.17 Соединение `mongodb.logs` и `MONGO_DATABASE_LOGS` удалены: логи читаются с диска, `migrate:fresh` эту базу больше не обходит.
- [x] 12.18 Память и диск: кэш WiredTiger по умолчанию 5 ГБ (`MONGO_WIRED_TIGER_CACHE_SIZE_GB`, было 10), zstd для всех коллекций MongoDB, `query_log` TTL 1 день. Проверка: прогон 1 млн до и после (с кэшем 1 ГБ, `max_server_memory_usage` 3 ГБ и `mark_cache_size` 256 МБ) — MongoDB до 653 МБ вместо 1.1 ГБ, пик диска буфера 124 МБ вместо 197 МБ, время то же (81 с), ошибок нет; лимиты ClickHouse затем оставлены прежними (5 ГБ, 512 МБ).
