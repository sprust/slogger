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
config/sconcur.php        — конфиг (async, scoped_services, http_server)
src/SConcurServiceProvider — провайдер (команды + проводка адаптеров в воркере)
src/Console/              — артизан-команды
src/Servers/              — MasterRunner (обёртка над SConcur\Worker\MasterCli)
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

Мастер и HTTP-сервер инстанцируются прямо в командах из `config('sconcur.http_server')`
(через `MasterConfig::fromArray`), без прокидывания JSON-пути.

```
sconcur:servers:master:start|stop|status|reload   # MasterRunner (supervisor, спавнит воркеры)
sconcur:servers:http:start                        # один HTTP-сервер в foreground (build + serve)
sconcur:extension:load                            # скачать .so (запускает downloader)
sconcur:extension:status                          # статус расширения (in-process)
```

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

Подключён через path-репозиторий в корневом `composer.json`. Публикация конфига:

```bash
php artisan vendor:publish --tag=sconcur-laravel
```

## Конфигурация (ENV)

Все значения `config/sconcur.php` берутся из ENV; дефолты — для dev/проекта.

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

### HTTP-сервер (`server`)

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
