# SConcur Laravel

Laravel-интеграция для [SConcur](../../../vendor/sconcur/sconcur): конкурентный HTTP-воркер и
coroutine-scoped приложение.

> Статус: **реализовано и проверено (план B3)**. `AsyncApplication` подключён глобально в
> `bootstrap/app.php` (drop-in subclass; при async=off — обычное поведение, прод/Octane/CLI/queue
> не затронуты). В воркере per-fiber изолированы `request`/`auth`/`session`/`cookie`, config-overlay,
> текущий маршрут, локаль, `View::share`, defer. Проверено под конкуренцией.
> DB: счётчик-методы не нужны на стандартном PHP (см. «Транзакции и async»).

## Зачем

SConcur исполняет каждый HTTP-запрос в отдельном PHP-Fiber конкурентно в одном процессе.
Octane-модель (clone `$app` + своп глобального контейнера) под такой конкуренцией не
fiber-safe. Этот пакет переносит per-request состояние в **контекст корутины**, не свопая
глобалки и не клонируя приложение.

Полный разбор и план — в [docs/fiber-safe-laravel-bridge.ru.md](docs/fiber-safe-laravel-bridge.ru.md).
ТЗ к библиотеке SConcur — в [docs/sconcur-coroutine-context.ru.md](docs/sconcur-coroutine-context.ru.md).

## Структура

```
config/sconcur.php        — конфиг (panel_host, scoped_services, http_server + groups)
src/SConcurServiceProvider — провайдер (команды + проводка адаптеров в воркере)
src/Console/              — артизан-команды
src/Servers/              — MasterRunner (обёртка над SConcur\Worker\MasterCli)
src/Queue/Rabbitmq/       — драйвер очереди и консьюмер-пул (Connector, Queue, Job, ConsumerRunner)
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

Мастер инстанцируется прямо в командах из `config('sconcur.http_server')`
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

## Транзакции и async (важно)

Не вызывай sconcur-async (Mongo / `Sleeper` / sconcur-Sql / HttpClient) **внутри открытой
MySQL-транзакции**. Блокирующий PDO общий на процесс: пока корутина держит транзакцию и
уходит в `await`, другая корутина сходит в то же физическое соединение — попадёт в твою
транзакцию или закроет её. Это порча данных, которую изоляция счётчика транзакций **не лечит**
(поэтому отдельные DB-методы и не добавляли).

Транзакции **без** async-await безопасны под конкуренцией: блокирующий PDO их сериализует
(проверено: 30/30 параллельных вложенных транзакций). Если нужна async-работа рядом —
выполняй её **до** `beginTransaction` или **после** `commit` (либо вынеси в очередь).

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
- [x] **Этап 4 — решение: DB-методы не нужны.** На стандартном PHP блокирующий PDO не уступает
  фибер, поэтому MySQL-транзакции **без** sconcur-await сериализуются и обычный `Connection`
  корректен под конкуренцией (проверено: 30/30 параллельных вложенных транзакций). Counter-scoping
  (`CoroutineTransactions`) давал ложную безопасность и удалён. Антипаттерн `await` **внутри**
  транзакции ломает общий физический PDO — что counter-scoping не чинит. **Правило: не делать
  sconcur-await внутри MySQL-транзакции** (для async-работы класть запрос в очередь/после commit).
- [x] **Нагрузочная проверка** под реальной конкуренцией: изоляция request/locale/config 30/30;
  MongoDB через sconcur 12/12 изолированы; вложенные MySQL-транзакции 30/30; антипаттерн
  `await`-в-транзакции воспроизведён.
