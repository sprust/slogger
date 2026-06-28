# SConcur Laravel

Laravel-интеграция для [SConcur](../../../vendor/sconcur/sconcur): конкурентный HTTP-воркер и
coroutine-scoped приложение.

> Статус: **каркас**. Воркер (`bin/sconcur-http-server`) рабочий; scoped-приложение
> (`AsyncApplication` + контекст) и артизан-команды — заготовки под план B3. Пакет подключён
> в корневой `composer.json` (path-репозиторий, symlink); провайдер регистрирует команды, но
> `AsyncApplication` ещё не активируется, поэтому на работающее приложение влияния нет.

## Зачем

SConcur исполняет каждый HTTP-запрос в отдельном PHP-Fiber конкурентно в одном процессе.
Octane-модель (clone `$app` + своп глобального контейнера) под такой конкуренцией не
fiber-safe. Этот пакет переносит per-request состояние в **контекст корутины**, не свопая
глобалки и не клонируя приложение.

Полный разбор и план — в [docs/fiber-safe-laravel-bridge.ru.md](docs/fiber-safe-laravel-bridge.ru.md).
ТЗ к библиотеке SConcur — в [docs/sconcur-coroutine-context.ru.md](docs/sconcur-coroutine-context.ru.md).

## Структура

```
bin/sconcur-http-server   — PSR-7 воркер (мост Octane ↔ SConcur HttpServer)
config/sconcur.php        — конфиг (async, scoped_services, http_server)
src/SConcurServiceProvider — провайдер (merge config + publish + команды)
src/Console/              — артизан-команды
src/Servers/              — MasterRunner (обёртка над SConcur\Worker\MasterCli)
src/Http/                 — HttpServerRunner + LaravelHttpHandler (build + serve)
src/Foundation/           — AsyncApplication, ScopedService, ScopedServiceProxy
src/Database/             — CoroutineTransactions (трейт)
docs/                     — ТЗ и план
```

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
standalone (dev). Обработчик пока не coroutine-safe — для одного процесса держать
`maxConcurrency=1` до переезда на `AsyncApplication`. Для прод-многоворкерного режима —
`master:start`.

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

## Открытые задачи

- DB-изоляция MySQL под SConcur (docs §6.4).
- Адаптеры: `AsyncRouter`, `AsyncTranslator`, `AsyncConfig`, `AsyncViewFactory`, `AsyncDispatcher`.
- Async-bootstrap, подменяющий `Application` на `AsyncApplication`, и чистка
  `clone`/`flush`/`CurrentApplication` из воркера.
