# SConcur Laravel

Laravel-интеграция для [SConcur](../../../vendor/sconcur/sconcur): конкурентный HTTP-воркер и
coroutine-scoped приложение.

> Статус: **реализовано и проверено (план B3)**. `AsyncApplication` подключён глобально в
> `bootstrap/app.php` (drop-in subclass; при async=off — обычное поведение, прод/Octane/CLI/queue
> не затронуты). В воркере per-fiber изолированы `request`/`auth`/`session`/`cookie`, config-overlay,
> текущий маршрут, локаль, `View::share`, defer. Проверено под конкуренцией.
> DB: соединение `sconcur_mysql` даёт ORM неблокирующий MySQL и транзакции per-coroutine
> (см. «База данных»); соединение `mysql` на PDO осталось со своим ограничением.

## Зачем

SConcur исполняет каждый HTTP-запрос в отдельном PHP-Fiber конкурентно в одном процессе.
Octane-модель (clone `$app` + своп глобального контейнера) под такой конкуренцией не
fiber-safe. Этот пакет переносит per-request состояние в **контекст корутины**, не свопая
глобалки и не клонируя приложение.

Полный разбор и план — в [docs/fiber-safe-laravel-bridge.ru.md](docs/fiber-safe-laravel-bridge.ru.md).
ТЗ к библиотеке SConcur — в [docs/sconcur-coroutine-context.ru.md](docs/sconcur-coroutine-context.ru.md).

## Структура

```
config/sconcur.php        — конфиг (panel_host, scoped_services, database, master + groups, queue, tasks)
src/SConcurServiceProvider — провайдер (команды + проводка адаптеров в воркере)
src/Console/              — артизан-команды
src/Servers/              — MasterRunner (обёртка над SConcur\Worker\MasterCli)
src/Queue/Rabbitmq/       — драйвер очереди и консьюмер-пул (Connector, Queue, Job, ConsumerRunner)
src/Database/Mysql/       — соединение sconcur_mysql (Connector, Connection, Dsn, TransactionStack)
src/Tasks/                — пул периодических задач (TaskPool, TaskPoolController, TaskRegistry)
src/Http/                 — HttpServerRunner + LaravelHttpHandler (build + serve)
src/Foundation/           — AsyncApplication, ScopedService, ScopedServiceProxy
src/Config/               — AsyncConfig (overlay config()->set per-coroutine)
src/Events/               — AsyncDispatcher (defer() per-coroutine)
src/Routing/              — AsyncRouter (current route/request per-coroutine)
src/Translation/          — AsyncTranslator (локаль per-coroutine)
src/View/                 — AsyncViewFactory (View::share per-coroutine)
docs/                     — ТЗ и план
```

Адаптеры подключаются **только** в воркере (`isHttpWorker()` по argv) — web/Octane/CLI/queue
не затронуты. Все per-request состояния (`request`/`auth`/`session`/`cookie`, config-overlay,
текущий маршрут, локаль, `View::share`, defer) живут в контексте корутины.

Контекст корутины берётся из библиотеки: `SConcur\Context\Context::current()`
(`find/has/set/forget`). Семантика — `vendor/sconcur/sconcur/docs/coroutine-context.ru.md`.

## Артизан-команды

Мастер инстанцируется прямо в командах из `config('sconcur.master')`
(через `MasterConfig::fromArray`), без прокидывания JSON-пути.

```
sconcur:servers:master:start|stop                 # MasterRunner (supervisor, спавнит воркеры)
sconcur:servers:master:status [--group=NAME]      # статус: все пулы или один
sconcur:servers:master:reload [--group=NAME]      # rolling restart: все пулы или один
sconcur:servers:http:start                        # один HTTP-сервер в foreground (build + serve)
sconcur:servers:rabbitmq:start                    # пул консьюмеров очереди в foreground
sconcur:rabbitmq:declare                          # объявить очереди и их очереди ожидания
sconcur:extension:load                            # скачать .so (запускает downloader)
sconcur:extension:status                          # статус расширения (in-process)
```

`reload` — единственная команда, которой нужен файл: мастер перечитывает конфиг с диска
в своём процессе, поэтому in-memory объект до него не доходит. `masterConfigPath()`
сериализует тот же самый массив в `{runtimeDir}/{name}.config.json` и отдаёт путь —
так файл, из которого мастер перезагружается, и конфиг, которым его супервизят,
не расходятся.

Мастер спавнит воркеры как `php artisan sconcur:servers:http:start --masterPid=N`
(`workerScript=artisan`, `workerArgs=[команда]`). Тот же `http:start` запускается и
standalone. Обработчик coroutine-safe (per-fiber контекст), запросы внутри процесса
обрабатываются конкурентно; для прод-многопроцессного режима — `master:start` (+ `reusePort`).

Источник подхода: coroutine-scoped модель (AsyncApplication + per-coroutine состояние)
взята из [yangusik/laravel-spawn](https://github.com/yangusik/laravel-spawn) (там — поверх
PHP TrueAsync) и адаптирована на стандартный PHP + SConcur (`Context::current()` вместо
TrueAsync-контекста). PSR-7 мост воркера — на базе модели Laravel Octane.

## Транзакции на PDO-соединении (важно)

Относится к обычному соединению `mysql`. У `sconcur_mysql` этого ограничения нет — см.
раздел «База данных».

Пока корутина держит транзакцию на блокирующем PDO, нельзя отдавать управление другой
корутине: PDO общий на процесс, и соседняя корутина сходит в то же физическое соединение
— попадёт в твою транзакцию или закроет её. Изоляция счётчика транзакций этого не лечит,
поэтому отдельных DB-методов и не добавляли.

Дело не в `await` как таковом, а в любом переключении корутины. Его вызывают:

- любой вызов, уходящий в Go-расширение, — Mongo, SQL-фича, HTTP-клиент, AMQP, `Sleeper`;
- `WaitGroup` — и на запуске дочерних корутин, и на ожидании их завершения;
- вытесняющее переключение по кванту (`preemption_quantum_ms` у пула задач): переключает
  даже чистый PHP-код, в котором нет ни одного вызова наружу;
- `Fiber::suspend()` в чужом коде — например, внутри вызванного пакета.

Практически на PDO под конкуренцией безопасна только транзакция, внутри которой не
происходит ничего, кроме SQL к тому же PDO, и то при выключенном вытеснении (проверено:
30/30 параллельных вложенных транзакций). Всё остальное — до `beginTransaction`, после
`commit` или в очередь.

## База данных (`sconcur_mysql`)

Соединение Laravel, за которым вместо PDO стоит SQL-фича SConcur. Statement уходит в
Go-расширение, пока вызвавшая корутина приостановлена, поэтому конкурентные обработчики в
одном процессе больше не стоят в очереди за одной блокирующей ручкой. Вне корутины те же
вызовы работают синхронно.

`Connection` наследует `Illuminate\Database\MySqlConnection`, поэтому грамматики, схема,
пост-процессор и `instanceof MySqlConnection` остаются на месте; заменены только методы,
которые полезли бы за ручкой PDO. Все они по-прежнему идут через `Connection::run()` —
замер времени, `QueryExecuted`, лог запросов и оборачивание в `QueryException` работают
как обычно.

### Конфигурация

```php
// config/database.php
'sconcur_mysql' => [
    'driver'   => 'sconcur_mysql',
    'host'     => env('DB_HOST'),
    'port'     => env('DB_PORT'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'strict'   => true,
    'max_open_conns' => 20,
],
```

`charset`, `collation`, `timezone`, `strict`/`modes` едут в DSN, а не отдельными `SET`
после подключения: их применяет сам go-драйвер. `parseTime` намеренно не включён — без
него `DATE`/`DATETIME`/`TIMESTAMP` приходят строкой `Y-m-d H:i:s`, которую ждёт
`Model::getDateFormat()`.

| ENV | Дефолт | Назначение |
|---|---|---|
| `SCONCUR_DB_CONNECTION` | `sconcur_mysql` | какое соединение подставить в `database.default`; пусто — не подставлять |
| `SCONCUR_DB_TIMEOUT_MS` | `30000` | предел на один statement; для курсора — на всю его жизнь |
| `SCONCUR_DB_MAX_OPEN_CONNS` | `20` | размер пула Go |
| `SCONCUR_DB_MAX_IDLE_CONNS` | `0` | простаивающих соединений; `0` — равно `max_open_conns` |
| `SCONCUR_DB_CONN_MAX_LIFETIME_MS` | `0` | время жизни соединения; `0` — без предела |

Пул с ограничением — не перестраховка: каждый одновременный statement берёт своё
соединение, поэтому безлимитный пул при фан-ауте упирается в `max_connections` сервера
(ошибка MySQL 1040).

### Подмена соединения по умолчанию

`config('sconcur.database.default_connection')` — имя соединения, на которое провайдер
переставляет `database.default` внутри корутинных процессов: HTTP-воркер,
пул консьюмеров и пул задач. Снаружи (миграции, tinker, php-fpm) настроенный дефолт
остаётся нетронутым, поэтому `artisan migrate` продолжает работать через PDO.

`config/queue.php` в `batching` и `failed` должен называть соединение как `null`, а не
через `env('DB_CONNECTION')`: `null` следует за `database.default`, иначе пул консьюмеров
писал бы `failed_jobs` через блокирующий PDO.

### Транзакции

Уровень вложенности живёт не в свойстве соединения (объект один на все корутины), а в
контексте корутины. Первый уровень — настоящий `BEGIN` фичи, выше — савпоинты, как и на
PDO.

- Сёстры друг друга не видят: конкурентные запросы и джобы — соседи в дереве контекста, а
  не предки, поэтому транзакция одной другой не видна.
- Дочерняя корутина транзакцию наследует, и её запросы идут в ту же транзакцию. Иначе
  `WaitGroup` из пяти `UPDATE` внутри `DB::transaction()` тихо ушёл бы пятью автокоммитами
  мимо неё.
- Закрывает транзакцию тот, кто её открыл. `commit()`/`rollBack()` корневого уровня из
  чужой корутины бросают исключение: закоммитить общий объект она бы смогла, а запись
  владельца в контексте осталась бы на месте, указывая на мёртвую транзакцию.
- Вложенный уровень дочерняя корутина открывает и закрывает сама. Имена савпоинтов берутся
  из счётчика, общего на транзакцию, а не из глубины: по глубине две сестринские корутины
  выдали бы одно имя, а MySQL при повторном `SAVEPOINT` удаляет прежний.

Что остаётся: недочитанный курсор (`cursor()`, прерванный до конца) держит соединение
транзакции, и следующая команда любой корутины в ней будет его ждать. При фан-ауте внутри
транзакции пользуйся `fetchAll`/`select`.

`afterCommit` и `dispatchAfterCommit` под конкуренцией пока некорректны:
`DatabaseTransactionsManager` — синглтон на процесс. Корутинная версия — отдельная работа.

### Отличия от PDO

- Типы. PDO с эмуляцией отдаёт всё строками; Go-сторона нормализует: целые → `int`,
  `FLOAT`/`DOUBLE` → `float`, `DECIMAL` → строка, `NULL` → `null`. Eloquent это скрывает,
  а строгое `===` со строкой в прикладном коде — нет.
- `getPdo()`/`getReadPdo()` бросают: ручки нет. Код, которому нужен PDO, работает через
  соединение `mysql`.
- `selectResultSets()` не поддерживается: фича отдаёт один result set на запрос.
- Строки всегда приходят как `stdClass` — это дефолтный `fetchMode` Laravel, менять его
  соединение всё равно не даёт.
- Read/write-разделение (`read`/`write`/`sticky`) не поддерживается — у фичи один DSN.
- `pretend()` держит флаг в общем объекте: в корутинном рантайме им пользоваться нельзя.
- Миграции и `schema:dump` остаются на `mysql`: `db:dump` зовёт `mysqldump` мимо
  соединения.

## Установка

Подключён через path-репозиторий в корневом `composer.json`. Конфиг обязателен:

```bash
php artisan vendor:publish --tag=sconcur-laravel
```

Пакет не мержит свой конфиг в приложение, поэтому опубликованный файл — это весь
`config('sconcur')`: приложение владеет каждым значением, включая дефолты. Мерж оставлял
бы за спиной приложения пакетные значения, и удалённый ключ тихо возвращался бы к
пакетному дефолту; а ещё пакету пришлось бы держать дефолты для того, чего он знать не
может, — какие очереди читать и с каким весом. Без публикации команды говорят об этом
прямо, а не падают на пустом конфиге.

В пакете лежит каркас: то, что верно для любого приложения. Детали — свои очереди, их
веса и число процессов — живут в опубликованном файле.

## Конфигурация (ENV)

Все значения `config/sconcur.php` берутся из ENV. Дефолты ниже — пакетные, из каркаса;
в опубликованном файле приложение ставит свои.

### Общие

| ENV | Дефолт | Назначение |
|---|---|---|
| `SCONCUR_ASYNC` | `false` | включить coroutine-scoped приложение (`AsyncApplication`) |

### Мастер (supervisor)

| ENV | Дефолт | Назначение |
|---|---|---|
| `SCONCUR_HTTP_WORKER_COUNT` | `1` | число воркеров (0 = по числу ядер) |
| `SCONCUR_HTTP_PHP_BINARY` | `php` | PHP-бинарь для воркеров |
| `SCONCUR_HTTP_PANEL_PORT` | `28081` | порт телеметрия-панели (0 = выкл) |
| `SCONCUR_HTTP_ADMIN_TOKEN` | `` (пусто) | Bearer-токен панели (пусто = выкл) |
| `SCONCUR_HTTP_NAME` | `sconcur-http-server` | имя сервера (lock/state/log файлы) |
| `SCONCUR_HTTP_ROTATE_DAYS` | `3` | ротация логов, дней |
| `SCONCUR_HTTP_LOG_TO` | `both` | куда логировать (`file`/`stdout`/`both`) |
| `SCONCUR_HTTP_RESTART_POLICY` | `always` | политика рестарта воркеров |
| `SCONCUR_HTTP_SHUTDOWN_TIMEOUT_MS` | `10000` | таймаут graceful-остановки воркера, мс |
| `SCONCUR_HTTP_RESTART_BACKOFF_MS` | `200` | стартовый backoff рестарта, мс |
| `SCONCUR_HTTP_MAX_RESTART_BACKOFF_MS` | `30000` | макс. backoff рестарта, мс |

### HTTP-сервер (блок `server` группы `http`)

| ENV | Дефолт | Назначение |
|---|---|---|
| `SCONCUR_HTTP_ADDRESS` | `0.0.0.0:28080` | адрес прослушивания |
| `SCONCUR_HTTP_REUSE_PORT` | `true` | `SO_REUSEPORT` (несколько процессов на один порт) |
| `SCONCUR_HTTP_MAX_REQUESTS` | `0` | стоп после N запросов (0 = ∞) |
| `SCONCUR_HTTP_MAX_CONCURRENCY` | `0` | макс. одновременных запросов (0 = ∞) |
| `SCONCUR_HTTP_MAX_REQUEST_BODY` | `10485760` | лимит тела запроса, байт |
| `SCONCUR_HTTP_READ_HEADER_TIMEOUT_MS` | `10000` | таймаут чтения заголовков, мс |
| `SCONCUR_HTTP_READ_TIMEOUT_MS` | `30000` | таймаут чтения, мс |
| `SCONCUR_HTTP_WRITE_TIMEOUT_MS` | `30000` | таймаут записи, мс |
| `SCONCUR_HTTP_IDLE_TIMEOUT_MS` | `60000` | idle-таймаут keep-alive, мс |
| `SCONCUR_HTTP_HANDLER_TIMEOUT_MS` | `60000` | таймаут обработки запроса, мс |
| `SCONCUR_HTTP_SERVER_SHUTDOWN_TIMEOUT_MS` | `5000` | таймаут остановки сервера, мс |

Не из ENV: `workerScript=base_path('artisan')`, `workerArgs=['sconcur:servers:http:start']`,
`phpArgs=[]`, `runtimeDir`/`logDir`=`storage_path('sconcur/runtime'|'sconcur/logs')`.

### Группы (SConcur 0.11)

Один мастер супервизит несколько непохожих пулов под одним локом и одним журналом,
поэтому `workerScript`, `workerCount`, `workerArgs` и `server` живут не на верхнем
уровне конфига, а в элементе списка `groups`.

Блок `server` группы мастер форвардит в argv её воркеров как есть, поэтому обе команды —
`http:start` и `rabbitmq:start` — объявляют эти флаги: artisan отвергает то, чего не
объявлено. Читают их `HttpServer::fromArgs` и `QueueConsumer::fromArgs`. Всё, что не
скаляр (список очередей), мастер кодирует в JSON по дороге.

Запуск без мастера форвардить некому, поэтому команда в этом случае берёт тот же блок
`server` из конфига своей группы. Группа ищется по тому, что она запускает, а не по
имени, — иначе переименование группы тихо оставило бы standalone-запуск на дефолтах
библиотеки.

## Очередь (`sconcur_rabbitmq`)

Драйвер очереди Laravel поверх AMQP-фичи SConcur плюс пул консьюмеров, который читает
очереди корутинами в одном процессе вместо одного блокирующего `queue:work` на воркера.
Выигрыш — на стороне консьюмера: и `ext-amqp`, и `php-amqplib` держат PHP-поток на
чтении очереди, а здесь подвешивается только своя корутина, поэтому один процесс тянет
несколько очередей, а медленная джоба стоит одного сообщения, а не воркера.

### Совместимость

Формат на проводе — не наш: тело, свойства сообщения и заголовок попыток ровно те, что
пишет `vladimir-yuldashev/laravel-queue-rabbitmq`. Джоба, отправленная любым из двух
драйверов, читается и выполняется другим — проверено в обе стороны.

Держится это на трёх вещах, и менять их нельзя в одностороннем порядке:

- счётчик попыток живёт в заголовке `laravel.attempts`, а не в `x-death`; на нём
  `Worker::process()` строит `maxTries` и запись в `failed_jobs`;
- очередь объявляется теми же флагами — `durable`, не `exclusive`, не `autoDelete`, без
  аргументов; расхождение даёт `406`, который закрывает канал;
- публикация идёт в дефолтный обменник с routing key, равным имени очереди.

### Соединение

```php
// config/queue.php
'sconcur_rabbitmq' => [
    'driver'    => 'sconcur_rabbitmq',
    'queue'     => env('RABBITMQ_QUEUE', 'default'),
    'dsn'       => env('SCONCUR_RABBITMQ_DSN'),   // amqp://user:pass@host:5672/%2f
    'delays_ms' => [1000, 5000, 30000, 300000],
],
```

`delays_ms` — лестница очередей ожидания, которую объявляет `sconcur:rabbitmq:declare`.
В AMQP нет отложенной публикации: `later()` и `release()` ходят через очередь, которую
никто не читает и которая по TTL отправляет сообщение обратно. Очередь на задержку, а не
одна с per-message TTL, потому что классическая очередь протухает только с головы.
Произвольная задержка округляется вверх до ближайшей объявленной.

Отложенная публикация всегда идёт через `publishConfirmed`, независимо от настроек
соединения. Она адресует очередь ожидания, а её легче всего забыть объявить: обычная
публикация на routing key, к которому никто не привязан, молча выбрасывается брокером —
и джоба, которую обработчик вернул в очередь через `release()`, исчезла бы без следа.
`publishConfirmed` по умолчанию mandatory, поэтому тот же случай бросает
`UnroutableMessageException`.

### Консьюмер

Пул — это группа мастера, поэтому он живёт под тем же супервизором, что и HTTP, и
отчитывается в ту же панель телеметрии (секция `consumers`).

```
php artisan sconcur:rabbitmq:declare
php artisan sconcur:servers:rabbitmq:start --queues='[{"name":"default","coroutineCount":8}]' --prefetchCount=1
```

Обработка идёт через `Illuminate\Queue\Worker::process()` — события джобы, `maxTries`,
`backoff` и `failed_jobs` достаются готовыми. `Worker::daemon()` не используется: это
строго последовательный цикл, одна джоба за раз, и его `sleep()` блокирует процесс.

Запись в `failed_jobs` делает не `Worker`, а команда `queue:work`, которую пул заменяет,
— поэтому `ConsumerRunner` вешает тот же слушатель `JobFailed` сам.

| ENV | Дефолт | Назначение |
|---|---|---|
| `SCONCUR_RABBITMQ_WORKER_COUNT` | `0` | процессов в пуле; меньше `1` — группа не попадает в конфиг мастера вовсе |
| `SCONCUR_RABBITMQ_QUEUES` | `[{"name":"default","coroutineCount":1}]` | очереди и их веса, JSON |
| `SCONCUR_RABBITMQ_PREFETCH_COUNT` | `1` | неподтверждённых сообщений на консьюмера |
| `SCONCUR_RABBITMQ_HANDLER_TIMEOUT_MS` | `0` | предел на одно сообщение в обработчике; `0` — без предела |
| `SCONCUR_RABBITMQ_REQUEUE_ON_FAILURE` | `false` | вернуть упавшее сообщение в очередь вместо dead-letter |
| `SCONCUR_RABBITMQ_MAX_MESSAGES` | `0` | дренировать и выйти после N сообщений |
| `SCONCUR_RABBITMQ_MAX_RUNTIME_SECONDS` | `0` | дренировать и выйти через N секунд |
| `SCONCUR_RABBITMQ_MAX_MEMORY_BYTES` | `0` | дренировать и выйти по размеру кучи |
| `SCONCUR_RABBITMQ_CONNECTION` | `sconcur_rabbitmq` | соединение `config/queue.php` для джоб |
| `SCONCUR_RABBITMQ_DECLARE_QUEUES` | `default` | что объявляет `sconcur:rabbitmq:declare`, через запятую |
| `SCONCUR_RABBITMQ_TRIES` | `1` | попыток до `failed_jobs` |
| `SCONCUR_RABBITMQ_BACKOFF` | `0` | задержка перед повтором, секунд |

Ноль в `SCONCUR_RABBITMQ_WORKER_COUNT` не значит «ни одного воркера»: для мастера
`workerCount: 0` — это воркер на ядро (`WorkerGroup`, `Cpu::count()`). Поэтому пул
выключается не нулём в группе, а тем, что группы в конфиге не оказывается.

Вес очереди — это то, чем в схеме с `queue:work` было число процессов на неё: сколько
консьюмеров она получает, каждый на своём канале. Обработчик при этом всё равно
выполняется в отдельной корутине на сообщение.

`handlerTimeoutMs` нулевой по умолчанию, потому что предел не замедляет джобу, которую
поймал, а отклоняет её, — решать это приложению, знающему свои джобы.

`handlerTimeoutMs` разматывает зависший обработчик и отклоняет его сообщение; воркер
берёт следующее. `WorkerOptions::$timeout` при этом ноль намеренно: `SIGALRM` воркера
Laravel убил бы процесс вместе со всеми обработчиками, работающими рядом.

## Этапы (план B3)

- [x] **Этап 1** — `AsyncApplication` активен в воркере; `request` per-fiber из контекста.
- [x] **Этап 2** — `auth`/`session`/`cookie` scoped через `AsyncApplication` + `ScopedServiceProxy`
  (session-драйвер `file` → отдельный handler не нужен; контекст per-fiber, сброс не требуется).
- [x] **Этап 3** — адаптеры `AsyncConfig`/`AsyncDispatcher`/`AsyncRouter`/`AsyncTranslator`/`AsyncViewFactory`.
- [x] **Этап 4 — решение: счётчик транзакций на PDO-соединении не чинится.** На стандартном PHP
  блокирующий PDO не уступает фибер, поэтому MySQL-транзакции без переключения корутины
  сериализуются и обычный `Connection` корректен под конкуренцией (проверено: 30/30 параллельных
  вложенных транзакций). Counter-scoping (`CoroutineTransactions`) давал ложную безопасность и
  удалён: переключение **внутри** транзакции ломает общий физический PDO, чего счётчик не чинит.
  Правило для соединения `mysql` — в разделе «Транзакции на PDO-соединении».
- [x] **Этап 5 — соединение `sconcur_mysql`.** Драйвер поверх SQL-фичи, уровень вложенности и
  открытая транзакция в контексте корутины, подмена `database.default` в корутинных процессах.
  Ограничение этапа 4 на нём не действует: транзакция закреплена за отдельным физическим
  соединением пула Go. См. «База данных».
- [x] **Нагрузочная проверка** под реальной конкуренцией: изоляция request/locale/config 30/30;
  MongoDB через sconcur 12/12 изолированы; вложенные MySQL-транзакции 30/30; антипаттерн
  `await`-в-транзакции воспроизведён.
