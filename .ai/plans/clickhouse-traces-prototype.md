# План: прототип хранения трейсов в ClickHouse

MongoDB держит в памяти кэш WiredTiger (`MONGO_WIRED_TIGER_CACHE_SIZE_GB=10`) и деградирует,
когда рабочий набор трейсов в него не влезает. Цель прототипа — на реальном объёме данных
сравнить MongoDB и ClickHouse по памяти, диску и скорости основных сценариев и принять
решение о переезде хранилища трейсов.

Прототип не заменяет MongoDB: receiver пишет в обе базы, PHP читает из ClickHouse только
за флагом. После прототипа либо пишется план полного переезда, либо всё удаляется.

## Критерии успеха

При одинаковом объёме данных (реальные 3 дня или синтетика того же порядка):

- RSS контейнера ClickHouse под нагрузкой ≤ 4–6 ГБ при лимите `max_server_memory_usage`;
  MongoDB для сравнения меряется с кэшем 10 ГБ и 2 ГБ.
- Размер трейсов на диске меньше, чем в MongoDB (данные + индексы).
- Графики (`FindTraceTimestampsAction`) за 24 ч и 3 дня — быстрее MongoDB.
- Список (`FindTracesAction`), первая страница — не медленнее 1.5× MongoDB с готовым индексом
  и быстрее MongoDB без динамического индекса.
- Детальная трейса по `tid` — ≤ 50 мс.
- Дерево на 10 тыс. и 200 тыс. узлов — не медленнее 2× MongoDB (с учётом вариантов из этапа 8).
- Receiver успевает за потоком: размер `buffer` не растёт при записи в обе базы.

## Решения

- MongoDB не удаляется ни в прототипе, ни при полном переезде, а возможно, и после него.
  Данные трейсов в ней сохраняются, receiver продолжает писать в неё. Вопрос об удалении
  решается отдельно, после окончания разработки.
- Версия ClickHouse — последняя LTS, не ниже 25.8 (тип `JSON` готов с 25.3, лёгкие `UPDATE`
  в бета с 25.8). Тег образа фиксируется при старте работ.
- Одна таблица `traces` на движке `ReplacingMergeTree(uat)`, ключ `(sid, tid)`, партиции
  по часу `lat`, TTL 3 дня. Коллекции по периодам, `tss`, динамические индексы и
  `_traceTreesView` в ClickHouse не переносятся.
- `dt` хранится дважды: `dt JSON` для фильтров и агрегаций, `dt_raw String` для показа
  (порядок ключей и точные типы). Если корень `dt` не объект, `dt = {}`, значение только в
  `dt_raw`.
- В прототипе receiver пишет в ClickHouse уже склеенный документ, который он записал в
  MongoDB: чтения из ClickHouse при записи нет. Склейка с чтением из ClickHouse меряется
  отдельно на этапе 8.
- Запись из receiver и из PHP идёт по HTTP-интерфейсу в формате `JSONEachRow`: один формат
  на обе стороны, без особенностей передачи `JSON` в нативном протоколе `clickhouse-go`.
- Ошибка записи в ClickHouse не ломает запись в MongoDB: она логируется и считается в
  статистике receiver.
- PHP ходит в ClickHouse через `SConcur\Features\HttpClient\HttpClient` — неблокирующий
  клиент под sconcur. Параметры запросов — только через `{name:Type}` и `param_<name>`.
- Чтение из ClickHouse включается флагом `TRACE_STORAGE=clickhouse` в
  `config/module-trace.php`, по умолчанию `mongodb`. API и фронтенд не меняются.
- Кэш дерева (`traceTreeCache`, `traceTreeCacheStates`), `traceMetrics`, `watcherTimelines`,
  `buffer` и прочие служебные коллекции в прототипе остаются в MongoDB.
- Профилирование (`pr`, `hpr`) и `cl` не переносятся: их никто не пишет.

## Как сейчас

- Receiver: `traces_transporter/transporter.go` читает пачку из `buffer` (1000 документов),
  группирует по сервису и трейсу, `periodic_trace_service.Save` для каждой трейсы делает
  `FindOne{sid,tid}` → склейку → upsert всего документа в почасовую коллекцию
  `traces_YYYY_MM_DD_HH_HH+1` (`trace_sharding_service`).
- PHP читает через `PeriodicTraceService` (обход коллекций) и репозитории
  `TraceRepository`, `TraceTimestampsRepository`, `TraceContentRepository`,
  `TraceTreeRepository`. Репозитории — конкретные классы без интерфейсов, регистрируются в
  `TraceServiceProvider::getContracts()`.
- Перед списком и графиками `TraceDynamicIndexInitializer` строит составной индекс по
  фильтруемым полям на всех коллекциях периода.
- `ClearTracesJob` раз в час удаляет коллекции старше `TRACES_LIFETIME_DAYS` (3).

## Этап 0. Базовый замер MongoDB

До любых изменений, чтобы было с чем сравнивать.

- Зафиксировать объём: число трейсов за сутки и за 3 дня, число сервисов, типов, тегов,
  средний и максимальный размер `dt`, число различных путей в `dt` (агрегация по
  `$objectToArray` рекурсивно на выборке 100 тыс. документов).
- Размер на диске: `$collStats` по всем коллекциям `tracesPeriodic` (данные + индексы).
- RSS `sl-mongo` в покое и под сценариями из этапа 7, при кэше 10 ГБ и 2 ГБ.
- Время сценариев из этапа 7 — с прогретым динамическим индексом и без него.
- Выбрать эталонные трейсы для замеров: мелкое дерево, дерево ~10 тыс. узлов, самое большое
  дерево (до 200 тыс.).

Результаты — в раздел «Результаты» в конце этого файла.

## Этап 1. Инфраструктура

### docker-compose

Сервис `clickhouse`:

```yaml
clickhouse:
  container_name: sl-clickhouse
  restart: unless-stopped
  image: clickhouse/clickhouse-server:<LTS>
  environment:
    CLICKHOUSE_DB: ${CLICKHOUSE_DATABASE}
    CLICKHOUSE_USER: ${CLICKHOUSE_USERNAME}
    CLICKHOUSE_PASSWORD: ${CLICKHOUSE_PASSWORD}
    CLICKHOUSE_DEFAULT_ACCESS_MANAGEMENT: 1
  ulimits:
    nofile: { soft: 262144, hard: 262144 }
  mem_limit: ${CLICKHOUSE_MEM_LIMIT:-6g}
  ports:
    - ${CLICKHOUSE_DOCKER_HTTP_PORT:-8123}:8123
  volumes:
    - ./docker/clickhouse/config.d:/etc/clickhouse-server/config.d:ro
    - ./docker/clickhouse/users.d:/etc/clickhouse-server/users.d:ro
    - clickhouse:/var/lib/clickhouse
```

Том `clickhouse` в `volumes`. Сервисы `app` и `receiver` получают `depends_on: clickhouse`.

### Настройки для малой памяти

`docker/clickhouse/config.d/low-memory.xml`:

- `max_server_memory_usage` — 4 ГБ (из env нельзя, значение в файле; меняется на этапе 7);
- `mark_cache_size` — 512 МБ, `uncompressed_cache_size` — 0;
- `index_mark_cache_size`, `index_uncompressed_cache_size` — 128 МБ;
- удалить `metric_log`, `asynchronous_metric_log`, `trace_log`, `text_log`, `part_log`
  (`remove="1"`);
- `query_log` оставить с TTL 3 дня: по нему считаются замеры.

`docker/clickhouse/users.d/profile.xml`, профиль `default`:

- `max_memory_usage` — 2 ГБ;
- `max_bytes_before_external_group_by`, `max_bytes_before_external_sort` — 1 ГБ;
- `max_threads` — 4;
- `do_not_merge_across_partitions_select_final` — 1;
- `async_insert` — 1, `wait_for_async_insert` — 1.

### Окружение

`.env.example`, `.env`: `CLICKHOUSE_HOST=sl-clickhouse`, `CLICKHOUSE_PORT=8123`,
`CLICKHOUSE_DATABASE=slogger`, `CLICKHOUSE_USERNAME`, `CLICKHOUSE_PASSWORD`,
`CLICKHOUSE_MEM_LIMIT`, `CLICKHOUSE_DOCKER_HTTP_PORT`, `TRACE_STORAGE=mongodb`.

`servers/receiver/.env.example`: `CLICKHOUSE_ENABLED=false`, `CLICKHOUSE_URL=http://sl-clickhouse:8123`,
`CLICKHOUSE_DATABASE`, `CLICKHOUSE_USERNAME`, `CLICKHOUSE_PASSWORD`.

### makefile

- `make clickhouse-client` — `docker exec -it sl-clickhouse clickhouse-client`.
- `make clickhouse-migrate` — `make art c="clickhouse:migrate"`.

## Этап 2. Схема

Миграции — файлы `database/clickhouse/NNN_<name>.sql`, применяются командой
`clickhouse:migrate` (`app/Console/Commands/Migrate/ClickhouseMigrateCommand.php`): таблица
`schema_migrations(version String, applied_at DateTime)`, файлы применяются по порядку, один
файл — одна команда.

`001_traces.sql`:

```sql
CREATE TABLE traces
(
    sid    UInt32,
    tid    String,
    ptid   String,
    tp     LowCardinality(String),
    st     LowCardinality(String),
    tgs    Array(LowCardinality(String)),
    dt     JSON(max_dynamic_paths = 1024),
    dt_raw String CODEC(ZSTD(3)),
    dur    Nullable(Float64),
    mem    Nullable(Float64),
    cpu    Nullable(Float64),
    lat    DateTime64(6, 'UTC'),
    cat    DateTime64(6, 'UTC'),
    uat    DateTime64(6, 'UTC'),

    INDEX tid_bf  tid  TYPE bloom_filter(0.01) GRANULARITY 1,
    INDEX ptid_bf ptid TYPE bloom_filter(0.01) GRANULARITY 1,
    INDEX tgs_bf  tgs  TYPE bloom_filter(0.01) GRANULARITY 1,
    INDEX tp_set  tp   TYPE set(1000)          GRANULARITY 4,
    INDEX st_set  st   TYPE set(100)           GRANULARITY 4,
    INDEX dur_mm  dur  TYPE minmax             GRANULARITY 4
)
ENGINE = ReplacingMergeTree(uat)
PARTITION BY toStartOfHour(lat)
ORDER BY (sid, tid)
TTL toDateTime(lat) + INTERVAL 3 DAY
SETTINGS ttl_only_drop_parts = 1;
```

- `ptid` — пустая строка вместо `NULL`: bloom-индекс и `IN` по нему проще.
- Трейса, у которой ещё не было create (update пришёл первым), пишется с `tp = '__UNKNOWN'`,
  как сейчас в MongoDB; `lat` берётся из `plat`, поэтому обе версии попадают в одну партицию
  и схлопываются.
- Варианты схемы для этапа 8 (projection по `ptid`, другой `index_granularity`) — отдельными
  миграциями, чтобы сравнивать на одних данных.

## Этап 3. Receiver: запись в обе базы

### Новые файлы

- `servers/receiver/internal/repositories/clickhouse_trace_repository/repository.go` —
  `Insert(ctx, rows []Row) error`: `POST /?query=INSERT INTO traces FORMAT JSONEachRow`
  с телом из строк JSON, basic auth, gzip тела, таймаут 30 с.
- `servers/receiver/internal/services/clickhouse_trace_service/service.go` — синглтон
  `Get()` по образцу остальных сервисов; `Enabled() bool` по `CLICKHOUSE_ENABLED`;
  `Collect(row)` копит строки; `Flush(ctx)` отправляет одной вставкой.

### Строка для ClickHouse

Собирается в `periodic_trace_service.saveTraces` из того же документа, который уходит в
upsert MongoDB:

- `sid, tid, ptid ('' вместо nil), tp, st, dur, mem, cpu`;
- `tgs` — массив имён из `tgs[].nm`;
- `dt` — объект `dt`, если это объект, иначе `{}`;
- `dt_raw` — `dt`, сериализованный в JSON с сохранением порядка ключей. `dt` хранится как
  `bson.D` (`internal/dto/data.go`): стандартный `json.Marshal` даст массив пар, нужен свой
  сериализатор `bson.D`/`bson.A` → JSON (новый хелпер `internal/helpers/json_helper`);
- `lat, cat, uat` — в формате `2006-01-02 15:04:05.000000`, UTC.

### Где вызывается

- `periodic_trace_service.Save` после успешного upsert трейсы кладёт строку в
  `clickhouse_trace_service.Collect`.
- `traces_transporter.Run` после `wg.Wait()` вызывает `Flush` — одна вставка на итерацию
  транспортёра по всем сервисам. Это даёт крупные пачки и не больше нескольких вставок в
  секунду.
- Ошибка `Flush` не влияет на `savedIds`/`failedIds`: логируется, строки выбрасываются,
  счётчик `clickhouseFailed` растёт.
- В `Stats` (`storage/stats.json`) добавляются `ClickhouseInserted`, `ClickhouseFailed`,
  `ClickhouseLastInsertMs`; `cmd/stats/main.go` их показывает.

### Проверки

- Go-тесты на сериализатор `bson.D` → JSON (порядок ключей, вложенные массивы, числа
  float64, null) и на сборку строки (не-объектный `dt`, пустые теги, nil-метрики).
- `go build ./...`, `go vet ./...`, `go test ./...` в `servers/receiver`.

## Этап 4. Заполнение данными

### Перенос из MongoDB

Команда `traces:clickhouse-backfill {--hours=72} {--batch=5000}`
(`app/Console/Commands/Local/ClickhouseBackfillCommand.php`):

- идёт по коллекциям `PeriodicTraceService::detectCollectionNames()` за период;
- читает документы курсором через sconcur Mongo, собирает строки тем же маппингом, что
  receiver (`dt_raw` — `json_encode` поля `dt` из BSON с сохранением порядка);
- вставляет пачками через `ClickhouseClient::insertJsonEachRow`;
- печатает прогресс по коллекциям и итог: строк, байт, секунд.

Повторный запуск допустим: `ReplacingMergeTree` схлопнет дубли, `uat` тот же.

### Синтетика

Если реального объёма мало — `FakeTracesCommand` (`app/Console/Commands/Local`) гонит поток
через receiver в обе базы. Цель — не меньше 50 млн трейсов за 3 дня с разными деревьями
(глубина до 50, ширина до 10 тыс.) и `dt` с вложенными объектами и массивами объектов.

## Этап 5. PHP-клиент ClickHouse

`app/Services/Clickhouse/`:

- `ClickhouseClient` — поверх `SConcur\Features\HttpClient\HttpClient`:
  - `select(string $sql, array $params, array $settings = []): iterable` —
    `POST /?database=…&default_format=JSONEachRow&param_x=…`, построчный разбор ответа;
  - `insertJsonEachRow(string $table, iterable $rows): void`;
  - `command(string $sql): void` — DDL для миграций;
  - ошибки ClickHouse (заголовок `X-ClickHouse-Exception-Code`, текст ответа) —
    `ClickhouseQueryException`.
- `ClickhouseConnectionConfig` — объект из `config('database.clickhouse')`.
- Каждый запрос получает `query_id` с префиксом сценария (`trace-list-…`,
  `trace-timestamps-…`), чтобы на этапе 7 находить его в `system.query_log`.

`config/database.php` — секция `clickhouse` (host, port, database, username, password,
timeout). Проверить в `code-analyse/deptrac-layers.yaml`, что `app/Services` разрешён для
`Repositories`.

## Этап 6. Чтение трейсов из ClickHouse за флагом

### Подмена репозиториев

- Для `TraceRepository`, `TraceTimestampsRepository`, `TraceContentRepository`,
  `TraceTreeRepository` выделяются интерфейсы с текущими публичными методами чтения
  (`Repositories/Contracts/*Interface`). Методы индексов, профилирования и удаления коллекций
  остаются только в MongoDB-реализации.
- Реализации для ClickHouse: `Repositories/Clickhouse/ClickhouseTraceRepository` и т. д.,
  возвращают те же DTO.
- `TraceServiceProvider` связывает интерфейсы с реализацией по `config('module-trace.storage')`.
- При `clickhouse` `TraceDynamicIndexInitializer::init()` в actions не вызывается: проверка
  флага в местах вызова (`FindTracesAction`, `FindTraceTimestampsAction`, `FindTypesAction`,
  `FindTagsAction`, `FindStatusesAction`).

### Построитель условий

`Repositories/Clickhouse/Services/ClickhouseTraceFilterBuilder` — аналог
`TracePipelineBuilder`, возвращает `WHERE` и параметры:

| Фильтр | SQL |
|---|---|
| `serviceIds` | `sid IN {sids:Array(UInt32)}` |
| `traceIds` | `tid IN {tids:Array(String)}` |
| `loggedAtFrom/To` | `lat BETWEEN {from:DateTime64(6)} AND {to:DateTime64(6)}` |
| `types`, `statuses` | `tp IN …`, `st IN …` |
| `tags` | `hasAll(tgs, {tags:Array(String)})` |
| `durationFrom/To` и т. п. | `dur >= … AND dur <= …` |
| `hasProfiling` | игнорируется, `true` → пустой результат |
| `data`: exists / null | `dt.<path> IS NOT NULL` / проверка через `JSONType(dt_raw, …)` |
| `data`: число | `dt.<path>.:Float64 <op> {v:Float64}` |
| `data`: строка contains / starts / ends / eq | `position`, `startsWith`, `endsWith`, `=` на `dt.<path>.:String` |
| `data`: bool | `dt.<path>.:Bool = {v:Bool}` |

- Путь из запроса проверяется по белому списку символов (`[A-Za-z0-9_]` и точки) до
  подстановки в SQL: пути нельзя передать параметром.
- Пути через массивы объектов. MongoDB сам проходит по массиву, ClickHouse требует
  `dt.items[].price`. Сервис `ClickhouseDataPathTypes` раз в 5 минут делает
  `SELECT distinctJSONPathsAndTypes(dt)` за последние сутки, кэширует в Redis; построитель по
  нему решает, где в пути массив, и строит `arrayExists(x -> …, dt.items[].price)`. Если
  путь встречается и как объект, и как массив — `OR` обоих вариантов.
- Разница null / отсутствие поля: проверить на этапе 7, различает ли их `JSON`. Если нет —
  условие через `JSONType(dt_raw, 'a', 'b') = 'Null'`.
- Все чтения — `FROM traces FINAL`. На этапе 8 сравнивается с чтением без `FINAL`.

### Сценарии

| Action | Запрос |
|---|---|
| `FindTracesAction` → `find` | `SELECT … WHERE … ORDER BY lat DESC, tid LIMIT {l} OFFSET {o}`; `data.fields` — `dt.<path>` в `SELECT` |
| `FindTraceTimestampsAction` → `TraceTimestampsRepository::find` | `GROUP BY toStartOfInterval(lat, INTERVAL …)` (для месяца `toStartOfMonth`); `count()`, `avg`, `min`, `max`, `quantiles(0.5, 0.95, 0.99)` по `dur/mem/cpu/dt.<path>.:Float64`; часовой пояс как сейчас в `TraceTimestampEnum` |
| `FindTypesAction`, `FindTagsAction`, `FindStatusesAction` | `GROUP BY tp` / `arrayJoin(tgs)` / `GROUP BY st` с `count()`, поиск по типу — `position(tp, …)` |
| `FindTraceDetailAction` → `findOneDetailByTraceId` | `WHERE tid = {tid} ORDER BY uat DESC LIMIT 1` (без `FINAL`), `dt_raw` → `TraceDataToObjectBuilder` |
| `FindTraceTreeAction` → `TraceTreeRepository` | `findParentTraceId`: `SELECT ptid WHERE tid = …`; потомки: `SELECT tid WHERE ptid IN {ids:Array(String)}` пачками по 1000; узлы — `findTreeNodesByTraceIds` одним запросом по `tid IN` |

Кэш дерева и его построение (`BuildTraceTreeCacheAction`) используют новые репозитории через
интерфейсы, сам кэш остаётся в MongoDB.

### Вне прототипа

Профилирование, `findTraceIds`, динамические индексы и их UI, Cleaner (удаление делает TTL),
статистика ClickHouse в Dashboard, `traceMetrics`, watchers.

### Проверки

- Юнит-тесты на `ClickhouseTraceFilterBuilder`: каждый фильтр из таблицы, пути через
  массивы, отказ на недопустимом пути, параметры вместо подстановки значений.
- Интеграционный тест репозиториев на тестовой базе ClickHouse: вставка набора трейсов →
  список, графики, типы, детальная, дерево дают то же, что MongoDB-реализация на тех же данных.
- `make check`. `make oa-generate` и `make frontend-npm-build` не нужны: контракт API не меняется.

## Этап 7. Замеры

Команда `traces:storage-bench {--storage=mongodb|clickhouse} {--runs=5}`
(`app/Console/Commands/Local/TraceStorageBenchCommand.php`) вызывает actions напрямую (без
HTTP), по каждому сценарию делает холодный и 5 тёплых прогонов, печатает p50/max и пишет
JSON в `storage/framework/bench/`.

Перед холодным прогоном: ClickHouse — `SYSTEM DROP MARK CACHE`, `SYSTEM DROP UNCOMPRESSED CACHE`;
MongoDB — перезапуск контейнера. Сброс кэша ОС — `sync; echo 3 > /proc/sys/vm/drop_caches`
на хосте, если есть доступ, иначе фиксируем, что прогоны с прогретым кэшем ОС.

### Сценарии

| # | Сценарий |
|---|---|
| 1 | Графики, шаг 1 мин, 24 ч, без фильтров: count, avg/p95 `dur` |
| 2 | То же за 3 дня, шаг 5 мин, фильтр по сервису и типу |
| 3 | Графики по `dt.<числовое поле>`, p95, 24 ч |
| 4 | Список, 3 дня, без фильтров, страницы 1 и 50 |
| 5 | Список с фильтрами `tags` + `dur > X` + строковый фильтр по `dt.*` |
| 6 | Список с фильтром по пути через массив объектов |
| 7 | Типы, теги, статусы за 3 дня |
| 8 | Детальная по `tid` (10 случайных трейсов) |
| 9 | Дерево: мелкое, ~10 тыс. узлов, максимальное |
| 10 | Поток записи: трейсов в секунду при записи в обе базы, размер `buffer`, `ClickhouseLastInsertMs` |

### Метрики

- Время: для ClickHouse ещё `read_rows`, `read_bytes`, `memory_usage` из `system.query_log`
  по `query_id`.
- Память: пик и покой `docker stats` для `sl-mongo` и `sl-clickhouse`.
- Диск: `SELECT sum(bytes_on_disk) FROM system.parts WHERE table = 'traces' AND active`
  против `$collStats` по `tracesPeriodic`; отдельно сжатие колонок `dt`, `dt_raw`
  (`system.columns`).
- Для MongoDB — с кэшем 10 ГБ и 2 ГБ, с динамическим индексом и без.
- Для ClickHouse — при `max_server_memory_usage` 4 ГБ и 2 ГБ.

## Этап 8. Варианты для сравнения

Каждый — отдельной миграцией или настройкой, замер сценариев, которых он касается.

1. **`FINAL`.** Чтение с `FINAL` и без; оценка, сколько дублей реально живёт (`count()` против
   `uniqExact(sid, tid)` по свежим партициям). Если дублей мало и они только в последнем
   часе — `FINAL` только для последних партиций.
2. **Дерево.** bloom-индекс по `ptid` против `ADD PROJECTION p_ptid (SELECT tid, ptid, lat
   ORDER BY ptid)`; `index_granularity` 8192 против 2048.
3. **Лимит путей `dt`.** Число путей из этапа 0; при превышении 1024 — сравнить
   `max_dynamic_paths` 1024 и 4096 по скорости вставки и фильтров по «редким» путям.
4. **Склейка без MongoDB.** Режим receiver `CLICKHOUSE_MERGE_SOURCE=clickhouse`: перед
   вставкой пачкой читает существующие строки `SELECT … FROM traces FINAL WHERE (sid, tid)
   IN (…)` и склеивает с ними. Мерить задержку итерации транспортёра и нагрузку на ClickHouse.
5. **Лёгкие `UPDATE`.** Только если вариант 4 не укладывается в поток: update пишется через
   `UPDATE traces SET … WHERE sid = … AND tid = …` (таблица с `enable_block_number_column`,
   `enable_block_offset_column`), число patch parts и влияние на чтение.

## Решение по итогам

Переезд оправдан, если выполнены критерии успеха, а дерево укладывается хотя бы в одном
варианте из этапа 8. Тогда пишется отдельный план полного переезда:

- receiver только в ClickHouse;
- судьба `buffer` и служебных коллекций (маленький MongoDB, MySQL или Redis);
- чтение переходит на ClickHouse, код MongoDB-пути остаётся рабочим за
  `TRACE_STORAGE=mongodb`, запись в обе базы сохраняется до отдельного решения;
- Cleaner и статистика Dashboard.

Если не оправдан — удаляется только ClickHouse-часть этого плана, MongoDB и её данные не
трогаются, кэш MongoDB уменьшается.

## Риски и открытые вопросы

- Пока MongoDB не удалена, её память остаётся на сервере. Для замеров память ClickHouse
  сравнивается с MongoDB по отдельности, а на время двойной записи кэш MongoDB можно
  уменьшить через `MONGO_WIRED_TIGER_CACHE_SIZE_GB`.
- Семантика фильтров по `dt` может отличаться от MongoDB в краевых случаях (null, массивы,
  разные типы по одному пути) — интеграционный тест на одинаковых данных обязателен.
- Выделение интерфейсов у четырёх репозиториев — единственное изменение в коде MongoDB-пути;
  его можно оставить и после прототипа.
- `HttpClient` sconcur: проверить потоковое чтение большого ответа (список дерева на
  200 тыс. узлов) и поведение при таймауте. Если ответ целиком грузится в память — ограничить
  выборки или добавить потоковый режим в sconcur.
- `max_server_memory_usage` задаётся в файле, не через env: для замеров при разных лимитах
  файл правится и контейнер перезапускается.
- Под sconcur правки PHP видны только после перезапуска воркера.
- Все запущенные для замеров процессы (sconcur, receiver, контейнеры) останавливаются после
  проверки.

## Файлы

Новые:

- `docker/clickhouse/config.d/low-memory.xml`, `docker/clickhouse/users.d/profile.xml`
- `database/clickhouse/001_traces.sql`
- `app/Console/Commands/Migrate/ClickhouseMigrateCommand.php`
- `app/Console/Commands/Local/ClickhouseBackfillCommand.php`
- `app/Console/Commands/Local/TraceStorageBenchCommand.php`
- `app/Services/Clickhouse/ClickhouseClient.php`, `ClickhouseConnectionConfig.php`,
  `ClickhouseQueryException.php`
- `app/Modules/Trace/Repositories/Contracts/*Interface.php` (4 шт.)
- `app/Modules/Trace/Repositories/Clickhouse/*Repository.php` (4 шт.)
- `app/Modules/Trace/Repositories/Clickhouse/Services/ClickhouseTraceFilterBuilder.php`,
  `ClickhouseDataPathTypes.php`
- `servers/receiver/internal/repositories/clickhouse_trace_repository/repository.go`
- `servers/receiver/internal/services/clickhouse_trace_service/service.go`
- `servers/receiver/internal/helpers/json_helper/helper.go`
- тесты для построителя, репозиториев, сериализатора и строки receiver

Изменяемые:

- `docker-compose.yml`, `.env.example`, `makefile`
- `config/database.php`, `config/module-trace.php`
- `app/Modules/Trace/Infrastructure/TraceServiceProvider.php`
- `app/Modules/Trace/Repositories/Trace*Repository.php` — `implements`
- actions с `TraceDynamicIndexInitializer::init()` — проверка флага
- `servers/receiver/.env.example`, `internal/services/periodic_trace_service/service.go`,
  `cmd/receiver/traces_transporter/transporter.go`, `cmd/receiver/main.go` (статистика),
  `cmd/stats/main.go`

## Порядок коммитов

1. ClickHouse в docker-compose, настройки памяти, env, makefile.
2. Миграции ClickHouse и таблица `traces`.
3. Receiver пишет трейсы в ClickHouse за `CLICKHOUSE_ENABLED`.
4. PHP-клиент ClickHouse и перенос данных из MongoDB.
5. Интерфейсы репозиториев трейсов.
6. Чтение трейсов из ClickHouse за `TRACE_STORAGE`.
7. Команда замеров.
8. Варианты из этапа 8 — по коммиту на вариант.

Каждый коммит — после `make check` (PHP) или `go build/vet/test` (receiver) и отдельного
согласования.

## Результаты

### Стресс-тест 2026-09-29

Нагрузка — `servers/receiver/cmd/loadgen`: генератор шлёт трейсы через сокет receiver тем же
протоколом, что `SocketClient` из `slogger/laravel`. Деревья повторяют то, что собирают
вотчеры: request, job, command и task в корне, под ними database, cache, event, model, gate,
log, http-client, mail, notification, dump, schedule. Около 2% деревьев приходят неудобно:
update раньше create, create дважды, create без update. Три сервиса, `lat` раскидан по
прошедшим часам. Машина: 16 ядер, 15 ГБ RAM, всё в одном docker-compose.

#### Прогон 1: исходная схема

Цель — 12M трейсов (500k/час за сутки). Остановлен на 3.67M.

| Что | Результат |
|---|---|
| Приём сокетом | 50k/с (1 воркер), 80k/с (8 воркеров), p99 ответа 61 мс |
| Транспортёр | 5k/с в начале часовой партиции, 2.5k/с к её концу |
| Память ClickHouse | упор в `max_server_memory_usage` 4 ГБ, 957 ошибок 241 за 30 мин |
| Потери | 4 447 из 3 669 921 (0.12%) — в `invalidBuffer` после 5 попыток |
| Диск | 592 МБ на 3.69M строк; `query_log` 426 МБ; каталог хранения 5.6 ГБ |
| Чтение при 1.1M | 0.03–0.36 с; первый фильтр по `data` после истечения кэша — 6 с |

Причины:

- `FindExisting` (`SELECT … FINAL … IN (1000 ключей)`) читал до 380k строк на пачку: ключи
  разбросаны по часу, каждый попадает в свою гранулу. Цена росла с размером партиции.
- `CacheWatcher` пишет `cache.<ключ>`, ключи кэша становились путями `dt`: 12 062 файла в
  part на 21 МБ, из них 10 208 — `dt.cache.*`. Слияние такой part брало 0.5–1.3 ГБ, их шло
  2–4 сразу.
- `query_log` хранил текст `FindExisting` с подставленными 1000 ключами — 52 КБ на запрос.
- `distinctJSONPathsAndTypes` по 100k строк — 5.8 с и 336 МБ на холодный кэш путей.

#### Изменения

- Receiver: половины трейса ждут друг друга в Mongo `pendingTraces` (TTL 3 ч). Финальные
  трейсы пишутся без чтения, вытесненная половина удаляется пакетным lightweight `DELETE` по
  точному `uat`. ClickHouse читается только для update без ожидающей половины.
- Receiver: пачка транспортёра 1000 → 5000 (`TRANSPORTER_BATCH_SIZE`).
- Receiver: ошибка недоступности хранилища (сеть, таймаут, коды 159, 202, 209, 210, 241,
  242, 252) не тратит попытки документов — пауза от 1 до 30 с и повтор.
- `dt JSON(max_dynamic_paths = 256, SKIP REGEXP '^cache\.')`.
- ClickHouse: `max_server_memory_usage` 5 ГБ, `background_pool_size` 4,
  `log_queries_cut_to_length` 10000.

#### Прогон 2: после изменений, 1M

| Что | Прогон 1 | Прогон 2 |
|---|---|---|
| Транспортёр | 2.5–5k/с | ~10k/с, 1M за 100 с |
| Пик памяти ClickHouse | 4 ГБ (упор) | 1.7 ГБ |
| Ошибки ClickHouse | 957 за 30 мин | 0 |
| Потери | 0.12% | 0 (1 001 686 из 1 001 686) |
| Строк без `FINAL` на уникальный трейс | — | 1 001 692 на 1 001 686 после очереди `DELETE` (~1 мин) |
| Рестарт ClickHouse посреди приёма 300k | 30k в `invalidBuffer` (до правки ретраев) | 0 потерь, `buffer` пуст через 30 с |

Чтение при 1M (секунды, три прогона, первый холодный):

| Сценарий | Время |
|---|---|
| Список без фильтра / по типу / по тегу | 0.06–0.09 |
| Список с фильтром `data` (число; путь через массив) | 0.38 холодный, 0.07 тёплый |
| График 1 день, шаг 5 мин / 1 час, все метрики | 0.13–0.21 / 0.08–0.15 |
| Фасеты | 0.03–0.11 |
| Детальная | 0.03 |
| Дерево 16 узлов до `finished` | 0.3 |
| Дерево 20 018 узлов до `finished` | 4.3 |

#### Прогон 3: пакетный `DELETE` на длинном потоке

На 12M очередь lightweight-`DELETE` (по мутации на проход транспортёра) заняла весь фоновый
пул: 884 мутации в очереди, слияния остановились, parts выросли до 1684, ClickHouse снова
упёрся в память (5 ГБ). На 1M этого не было видно: очередь расходилась за минуту после
приёма. `DELETE` убран: старшая половина трейса живёт до слияния, чтение идёт с `FINAL`, а
`OptimizeTracesJob` раз в час сливает закрытые часы в одну part.

#### Прогон 4: без `DELETE`, с `OPTIMIZE … FINAL`, 12M

| Что | Результат |
|---|---|
| Приём | 12 019 381 трейс за 16 мин, ~12.4k/с |
| Потери | 0: `uniqExact(sid, tid)` = отправлено |
| Пик памяти ClickHouse (MemoryTracking) | 1.8 ГБ |
| Parts | до 146 активных, не больше 14 на партицию |
| Ошибки ClickHouse | 0 |
| `OptimizeTracesJob` под заливкой | 24 партиции за 179 с, до 10.3 с на одну, память до 1.5 ГБ, ошибок 0 |
| Диск | 1.57 ГБ на 12M строк |
| `pendingTraces` | ~3.9k — create без update, 0.5% родителей по сценарию генератора |

Дерево в 200 018 узлов (корень и 200k детей): построение до `finished` за 56 с, ответ `/tree`
ленивый, страница детей по 100 — 0.02–0.04 с. Пики: ClickHouse 2.3 ГБ, воркеры 362 МБ,
MongoDB 1.4 ГБ.

Осталось:

- Код 60 (`UNKNOWN_TABLE`) тратит попытки документов: во время `migrate:fresh` 106 собственных
  трейсов панели ушли в `invalidBuffer`.
- Карта путей для фильтра по `data` строится в запросе пользователя, раз в 5 минут.
- У очереди `trace-tree` один обработчик: маленькое дерево ждёт, пока строится большое.
