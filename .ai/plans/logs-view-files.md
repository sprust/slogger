# План: страница логов читает файлы напрямую

Страница `/logs` сейчас показывает коллекцию `logs` в MongoDB, куда пишет канал `mongodb`
из стека логирования. Цель — читать сами файлы, как это делает
[opcodesio/log-viewer](https://github.com/opcodesio/log-viewer): файлы Laravel и файлы
nginx, через фичу Files из `sconcur/sconcur` (`SConcur\Features\Files\Files`).

## Решения

- Watcher `logErrors` переходит на файловые индексы. MongoDB для логов больше не нужна.
- Коллекция `logs` удаляется миграцией.
- Индексы хранятся файлами в `storage/framework/logs-index`, пишутся и читаются через
  sconcur Files.
- Просмотр и скачивание. Удалять из UI можно только файлы типа `laravel` (см. «Удаление
  файлов»).
- Кроме логов Laravel читаются логи nginx: access и error.
- Просмотр и поиск работают по нескольким файлам сразу: выбранным, всем файлам источника
  или всем файлам. Один файл — частный случай.

## Как сейчас

- `config/logging.php`: стек `daily` + `mongodb`, отдельный канал `slogger` (daily,
  `storage/logs/slogger/slogger.log`).
- `MongodbLogHandler` → `CreateLogAction` → `LogRepository::create()` пишет документ в
  `logs`.
- `LogController::index()` → `PaginateLogsAction` → `LogRepository::paginate()`: offset,
  20 на страницу, regex по `message`, фильтр по уровню свободным текстом.
- `LogErrorsChecker` → `FindLogErrorStatAction` → `LogRepository::findLevelStatBetween()`:
  число записей ERROR и выше за окно и текст последней.
- nginx (`nginx:alpine`) пишет в `/var/log/nginx/access.log -> /dev/stdout` и
  `error.log -> /dev/stderr`, файлов нет.

## Источники логов

`config/module-logs.php` (по образцу `module-trace.php`), список источников без пересечений:

```php
'sources' => [
    ['folder' => storage_path('logs'),         'pattern' => '*.log',        'type' => 'laravel'],
    ['folder' => storage_path('logs/slogger'), 'pattern' => '*.log',        'type' => 'laravel'],
    ['folder' => env('LOGS_NGINX_PATH', storage_path('logs/nginx')), 'pattern' => 'access*.log', 'type' => 'nginx_access'],
    ['folder' => env('LOGS_NGINX_PATH', storage_path('logs/nginx')), 'pattern' => 'error*.log',  'type' => 'nginx_error'],
],
'index_path' => storage_path('framework/logs-index'),
'per_page'   => 50,
'search'     => ['time_budget_ms' => 2000, 'bytes_budget' => 64 * 1024 * 1024, 'concurrency' => 4, 'max_files' => 200],
'nginx_days' => 14,
```

Фактический конфиг — `config/module-logs.php`: у источника ещё есть `name` (группа на
странице), `type` задаётся case-ом `LogTypeEnum`, у каждого ключа комментарий с
допустимыми значениями.

Список файлов строится через `Files::list(folder, pattern, withMetadata: true)` — один
вызов на источник, без рекурсии.

### nginx пишет в файлы

- `docker-compose.yml`: у `nginx` появляется том `./storage/logs/nginx:/var/log/nginx/app`
  и скрипт `docker/nginx/entrypoint.d/10-logs-dir.sh`. Образ nginx исполняет скрипты из
  `/docker-entrypoint.d/` перед стартом. Скрипт создаёт каталог и даёт на него права
  `0777`: воркеры nginx работают от пользователя `nginx` и должны создавать там файлы.
- `docker/nginx/templates/default.conf.template`:
  - `map $time_iso8601 $log_date { "~^(?<d>\d{4}-\d{2}-\d{2})" $d; default today; }`;
  - `access_log /var/log/nginx/app/access-$log_date.log combined;` и
    `open_log_file_cache max=4;` — ежедневные файлы без logrotate;
  - `error_log /var/log/nginx/app/error.log warn;` — `error_log` не принимает переменных,
    это один файл.
- Старые access-файлы удаляет автоочистка раз в сутки (см. «Автоочистка»). Удалять
  можно: каталог принадлежит пользователю хоста. `error.log` не ротируется — ограничение, записано в
  README. На проде путь задаётся `LOGS_NGINX_PATH`, ротация остаётся за хостом.

### Как сделано (шаг 7)

- `access_log` с переменной в пути пишется, только пока существует `root` запроса. По
  умолчанию это `/etc/nginx/html`, которого в `nginx:alpine` нет, и файлы молча не
  создавались бы. Поэтому в `server` задан `root /usr/share/nginx/html`.
- Глобальный `error_log` из `/etc/nginx/nginx.conf` (старт, reload, ошибки конфига)
  остаётся в stderr контейнера. Access-лог в `docker logs` больше не выводится.
- В `.env.example` добавлены `LOGS_NGINX_KEEP_DAYS` и закомментированный
  `LOGS_NGINX_PATH`: пустое значение `env()` вернул бы пустой строкой, а не значением по
  умолчанию.

## Форматы

Интерфейс `LogFormatInterface` в `Domain/Services/Formats` и по реализации на тип:

| Тип | Начало записи | Уровень | Поля строки |
| --- | --- | --- | --- |
| `laravel` | `^\[Y-m-d H:i:s(.u)?(±hh:mm)?\] env.LEVEL: ` | DEBUG…EMERGENCY | время, env, уровень, первая строка, полный текст, контекст (хвостовой JSON первой строки) |
| `nginx_access` | каждая строка, формат `combined` | класс статуса: 1xx…5xx | время, IP, метод, путь, протокол, статус, байты, referer, user agent |
| `nginx_error` | `^Y/m/d H:i:s \[level\] ` | debug, info, notice, warn, error, crit, alert, emerg | время, уровень, pid#tid, `*connection`, сообщение, client, server, request, upstream, host |

- Записи Laravel многострочные (`[stacktrace]`). Запись длится до следующего заголовка.
  Байты до первого заголовка становятся записью без уровня.
- Строки nginx, которые не разобрались, остаются записями без уровня: текст есть, полей
  нет.
- Уровень хранится в индексе как `uint8` — номер case в enum своего типа:
  `LaravelLogLevelEnum`, `NginxErrorLogLevelEnum`, `HttpStatusClassEnum`. Уровни ERROR и выше
  для watcher-а есть только у `laravel`.

## Индекс

Каталог `storage/framework/logs-index/<id>/`, где `id` = `sha1` абсолютного пути файла:
nginx-логи на проде могут лежать вне `base_path()`.

- `meta.json` (`Files::writeAtomic`): путь, тип, версия формата индекса, проиндексированные
  байты, `md5` первых 1024 байт файла, число записей, счётчики по уровням.
- `entries.idx`: записи по 21 байту, `pack('NJNNC', entryNo, offset, length, unixTime, level)`.
  Дописывается через `Files::openWriter(mode: FileWriteMode::Append)`.
- `level-<n>.idx`: те же записи, только уровня `n`. Счётчик уровня = размер / 21. Страница
  одного уровня — один `Files::read(offset, length)` по индексу.

### Свежесть

`LogIndexer::ensureFresh()` перед каждым чтением:

1. `Files::stat(path)`.
2. Размер равен проиндексированному → индекс свежий.
3. Файл вырос, голова совпадает → последняя запись удаляется из индексов (`Files::truncate`
   на 21 байт; она могла быть дописана не до конца), файл читается окнами
   `Files::read(offset, 4 MiB)` с её начала, новые записи дописываются.
4. Файл уменьшился или голова не совпала (ротация, подмена) → индекс строится заново.

- Заголовки в окне ищутся одним `preg_match_all(..., PREG_OFFSET_CAPTURE)`. Хвост окна
  после последнего заголовка переносится в следующее окно.
- `readLines`/`readChunks` не подходят: они не начинают с произвольного offset и не
  отдают байтовые позиции.
- Параллельная индексация одного файла закрыта мьютексом `LogIndexMutex` (см.
  «Мьютекс»). Кто ждал, после захвата снова проверяет свежесть.
- Индексы файлов, которых больше нет, удаляет автоочистка раз в сутки. Список файлов
  ничего не удаляет: он только читает.

### Как сделано (шаг 2)

- `meta.json` пишется один раз — в конце прохода или при остановке по бюджету, а не после
  каждого окна: `writeAtomic` делает fsync, и на мелких окнах это было основное время.
  Записи, дописанные в `.idx` сверх `meta` (прерванный проход), срезаются в начале
  следующего прохода по счётчикам из `meta` — и в `entries.idx`, и в каждом
  `level-*.idx`.
- В `meta` есть `lastEntryOpen`: последняя запись дочитана до конца файла и могла быть
  недописана. Только тогда она срезается и перечитывается; после остановки по бюджету
  проход продолжается с `indexedBytes`.
- Запись длиннее окна: окно удваивается до 64 MiB, дальше запись режется по окну, а
  остаток становится записью без уровня.
- Строка без своего времени (мусор до первого заголовка, неразобранная строка nginx)
  берёт время предыдущей записи, в начале файла — первой найденной, иначе `mtime`.
- Построчные форматы (`AbstractLineLogFormat`) берут одну регулярку на окно с двумя
  группами и без `PREG_OFFSET_CAPTURE`: смещения строк считаются нарастающим итогом.
  Разобранное время кэшируется на одинаковые подряд метки.
- Замер (php-fpm, `XDEBUG_MODE=off`, прогретый кэш): access-лог 200 МБ, 1.36 млн
  строк — 1.4 с, пик 66 МБ; laravel-лог 200 МБ, 14 тысяч записей — 0.5 с. Проверка
  свежего индекса — один `stat`, 0.5 мс.

## Автоочистка

Одна команда `logs:clean` (`Infrastructure/Commands/CleanLogsCommand` →
`CleanLogsAction`), в `Console\Kernel` раз в сутки (`->daily()`,
`->withoutOverlapping()`):

1. Удаляет nginx `access-*.log` старше `nginx_days` (`Files::delete(missingOk: true)`).
   Файлы Laravel не трогает: их по `days` удаляет сам daily-канал Monolog.
2. Проходит `index_path` (`Files::list`) и удаляет каталоги индексов, чей файл больше не
   входит ни в один источник (`Files::removeDirectory(recursive: true)`).
3. Пишет в лог, сколько удалено файлов и индексов.

Каталог индекса удаляется под тем же мьютексом `LogIndexMutex`, что и индексация,
чтобы не удалить его посреди дозаписи.

### Как сделано (шаг 6)

- Срок хранения — не отдельный `nginx_days`, а ключ источника `keep_days` в
  `config/module-logs.php` (`LOGS_NGINX_KEEP_DAYS`, 14 для access-логов nginx). У
  источников Laravel его нет: файлы удаляет daily-канал Monolog по своему `days`.
  `logs:clean` удаляет файлы источника, не менявшиеся дольше `keep_days`.
- `logs:clean` (`CleanLogsCommand` → `CleanLogsAction`, `->daily()->withoutOverlapping()`)
  сначала удаляет просроченные файлы, затем каталоги индексов, чьих файлов больше нет
  среди источников (`LogIndexRepository::findFileIds()`), — так индекс удалённого файла
  уходит в тот же проход. Индекс, чей мьютекс занят дольше секунды, остаётся до
  следующего раза.
- `logs:index` (`IndexLogsCommand` → `IndexLogsAction`, `->everyMinute()->withoutOverlapping()`)
  дописывает индексы всех файлов всех источников без бюджета, параллельно по
  `search.concurrency`. На живых логах: 19 файлов, 3.7 МБ.
- Команды запускает `schedule:run` из `CronTask` пула задач; Laravel выполняет
  `command()` отдельным процессом `php artisan`, вне корутин.

## Мьютекс

Сделан первым шагом. Основа — реализация из другого проекта пользователя, перенесённая в
`Common` и адаптированная под SConcur.

- `Common/Domain/Services/Mutex/AbstractMutex` — ключ, `getMaxLockSec()` (10),
  `getWaitForBlockSec()` (15), `getAfterLockHandler()`, `getBeforeReleaseHandler()`.
- `Common/Domain/Services/Mutex/MutexManagerInterface` — `lock(AbstractMutex)` и
  `unlock(AbstractMutex)`. Вызывающий код пишет `lock()` и `try { ... } finally { unlock() }`.
- `Common/Domain/Exceptions/MutexLockTimeoutException extends RuntimeException`.
- `Common/Infrastructure/Services/MutexManager`, привязка в
  `Common/Infrastructure/CommonServiceProvider`.
- Хранилище блокировок — `cache.mutex_store` (`MUTEX_CACHE_STORE`, по умолчанию
  `sconcur_redis`). Берётся один раз в конструкторе. Только у `sconcur_redis` `block()`
  ждёт через `Sleeper` и отпускает воркер.

Против оригинала:

- Событий нет.
- Держатель мьютекса (`Lock`, уровень, `spl_object_id` файбера) хранится в
  `SConcur\Context\Context` под ключом `mutex:<key>`, а не в `static`. Повторный вход —
  только в том же файбере: соседняя корутина и ребёнок из `WaitGroup` захватывают заново.
- Упавший `afterLock`-обработчик освобождает блокировку. Упавший `beforeRelease`
  не мешает освобождению.
- Корутина, завершившаяся с мьютексом, держит его в Redis до `maxLockSec`.

### Мьютексы этого плана

- `LogIndexMutex(fileId)`: ключ `logs-index:<id>`, `maxLockSec` 300 — первая индексация
  большого файла идёт дольше 10 секунд, а истёкшая посреди дозаписи блокировка пустила бы
  второго индексатора в тот же `.idx`. `waitForBlockSec` 20.
- Не дождались в HTTP-запросе (`MutexLockTimeoutException`) — ответ с `indexing: true`,
  фронт повторяет запрос.
- Не дождались в watcher-е — счёт по индексу как есть, без дозаписи: он отстаёт на
  минуту, а не на весь файл.
- `logs:clean` не дождался — каталог пропускается до следующего раза.

## Чтение одного файла

Поток записей одного файла — `LogFileStream`. Он идёт назад (или вперёд для `newer`) от
позиции и отдаёт записи, прошедшие фильтры. Поток по нескольким файлам собирается из
таких потоков (см. ниже).

- Порядок — новые сверху. Позиция в файле — номер записи.
- Без фильтра по уровню: срез `entries.idx`. Записи идут подряд, их байты читаются одним
  `Files::read` (если диапазон больше 8 MiB — по записи).
- Один уровень: срез `level-<n>.idx`.
- Несколько уровней: в каждом `level-<n>.idx` бинарным поиском по `entryNo` находится
  позиция, блоки читаются назад и сливаются по `entryNo`. Байты разбросанных записей
  читаются параллельно в `WaitGroup`.
- Период `from` / `to`: бинарный поиск по `unixTime` в индексе даёт диапазон номеров
  записей, дальше читается только он.
- Поиск — подстрока без учёта регистра. Индекс читается блоками по 500 записей, байты
  блока — одним `read`, совпадения проверяются в PHP. Следующий блок читается параллельно
  с разбором текущего.
- Скачивание: `Files::readChunks` в `StreamedResponse` (в `sconcur/laravel` 0.7 стриминг
  настоящий).

### Как сделано (шаг 3)

- `LogFileStreamFactory::make(file, meta, filter, direction, position)` строит
  `LogFileStream`: по дорожке на `entries.idx` (без фильтра уровней) или на каждый
  выбранный `level-<n>.idx`. Границы дорожек — бинарным поиском (`LogIndexSearch`) по
  номеру записи и по времени; последние 512 записей диапазона дочитываются одним `read`.
  Счётчики берутся из `meta`, а не из размера `.idx`.
- `LogFileStream::next(maxRecords)` просматривает до `maxRecords` записей (слияние дорожек
  по `entryNo`), читает их тексты и отдаёт подходящие. Для merge по файлам наружу
  выведены `getPosition()` (курсор — следующая запись), `getNextTime()`, `getLastTime()`,
  `getScannedRecords()`, `getScannedBytes()`, `getTotal()`.
- `LogTextReader` склеивает записи в диапазоны (разрыв до 64 КиБ, диапазон до 8 МиБ) и
  читает несколько диапазонов параллельно в `WaitGroup`. Текст записи режется до
  `module-logs.reading.max_entry_bytes` (1 МиБ), у записи флаг `truncated`.
- Поиск: `stripos`, для запроса не из ASCII — `mb_stripos`.
- Формат разбирает запись на поля: `LogFormatInterface::parseEntry()` →
  `LogEntryDetailsObject` (сообщение, контекст, поля типа) и `getLevelNames()`. Контекст
  Laravel — хвостовой JSON, переводы строк внутри стектрейса экранируются перед
  `json_decode`.
- Замер без xdebug: поиск по access-логу 200 МБ просматривает ~59 МБ/с (806 тысяч
  записей за 2 с); страница из 50 записей из середины файла — 1 мс.

## Несколько файлов

Область запроса — список `id` файлов: явный выбор, все файлы источника или все файлы.
Фронт разворачивает источник в `id` сам, бэкенд принимает только `files[]`.

### Порядок по времени

Выдача по нескольким файлам упорядочена по `unixTime`, затем по файлу и `entryNo`. Файлы
сливаются k-way merge по потокам `LogFileStream`:

- У каждого потока есть фронт — время последней просмотренной записи. Совпадение из
  файла A отдаётся в страницу, только когда оно не старше фронтов всех остальных
  незакончившихся потоков: ни один из них уже не найдёт ничего новее.
- Файл, чья самая новая запись (последняя запись индекса) старше текущего кандидата,
  не читается, пока до него не дойдёт очередь. Ежедневные файлы не пересекаются по
  времени, поэтому поиск по ним вырождается в «файл за файлом, от нового к старому».
  Файлы одного дня (laravel, slogger, nginx) перемежаются по времени.
- Потоки, чья очередь пришла, читаются параллельно в `WaitGroup`, не больше
  `search.concurrency` (по умолчанию 4) одновременно.

### Курсор

Непрозрачная строка: base64 от JSON `{"v":1,"d":"older","f":{"<id>":<entryNo>,...}}`, где
для каждого файла записана позиция, с которой продолжать, включительно. `-1` — файл
закончился.

- У файла, из которого совпадения отдавались, позиция — сразу за последним отданным.
- У файла с найденными, но не отданными совпадениями позиция — на первом неотданном: на
  следующем запросе он перечитается. Лишнее чтение небольшое: блок.
- У файла без совпадений позиция — его фронт: всё до фронта просмотрено и пусто.
- Курсор проверяется строго: версия, направление, `id` только из области запроса, числа
  в пределах индекса. Иначе 422. Файл, пропавший из списка (ротация), выпадает из
  курсора молча.
- Индекс файла, перестроенный между запросами (ротация, подмена головы), делает его
  позицию недействительной. Такой файл начинается заново, в ответе флаг `restarted`.

### Бюджет и прогресс

- Бюджет общий на запрос: `search.time_budget_ms` и `search.bytes_budget` на все файлы
  вместе. Исчерпан — ответ с тем, что найдено, и `next_cursor`.
- `total` — число записей в области с учётом уровней и периода. Без поиска это точное
  число результатов, с поиском — знаменатель для `scanned`. Считается по счётчикам
  уровней и бинарному поиску по времени, без чтения файлов.
- Без `search_query` тот же механизм даёт объединённую ленту нескольких файлов.

### Уровни в нескольких файлах

У каждого типа свои уровни, поэтому фильтр — список ключей `<type>.<level>`:
`laravel.ERROR`, `nginx_access.5xx`, `nginx_error.crit`. Файл берёт из фильтра только
ключи своего типа. Если фильтр задан, а для типа файла в нём ключей нет, файл ничего не
отдаёт: выбранные уровни — это ровно то, что показывается. Пустой список — фильтра нет.
Уровень 0 (запись без уровня) — ключ `<type>.none`.

### Индексация перед многофайловым запросом

- `ensureFresh` для всех файлов области — параллельно в `WaitGroup`, с тем же
  ограничением `search.concurrency`.
- Первая индексация большой области может съесть минуты. Поэтому команда `logs:index`
  (`Console\Kernel`, раз в минуту) держит индексы всех источников свежими, и запрос
  обычно дописывает несколько килобайт.
- Если индексация в запросе не уложилась в бюджет, ответ приходит без записей, с
  `indexing: true` и прогрессом. Фронт повторяет запрос.

### Как сделано (шаг 4)

- `LogEntriesMerger` — k-way merge по `LogMergeSlot` (поток + отложенные совпадения).
  На каждом шаге головы слотов (`LogMergeHeadObject`: отложенное совпадение или время
  следующей непросмотренной записи) сортируются; если первая — совпадение, оно уходит в
  страницу, иначе продвигаются все потоки, чьи головы идут раньше первого совпадения (не
  больше `search.concurrency`, параллельно в `WaitGroup`). Бюджет проверяется только после
  первого продвижения, так что запрос всегда двигает курсор.
- При равном времени порядок — по порядку файлов в запросе; в направлении `newer` обратный,
  чтобы после разворота страница совпадала со страницей `older`.
- Курсор: `older_cursor` и `newer_cursor` в каждом ответе. Для страницы `older` курсор
  `newer` — стартовые позиции + 1 (для первой страницы — текущий конец файлов: «Новее»
  покажет дописанное после). Файл без позиции в курсоре начинает с начала своего
  направления.
- Смена файла под курсором определяется по голове: у позиции курсора хранится
  `headLength` и `headHash` индекса; при другом `headLength` голова перечитывается.
- `search.block_records` (1000) — сколько записей файла поиск читает между проверками
  бюджета.
- Структуры — объектами, без массивов со смыслом в ключах: `LogLevelCountObject`,
  `LogLevelNameObject`, `LogEntryFieldObject`, `LogFilePositionObject`,
  `LogFileIndexObject`, `LogFileStartObject`, `LogTextRangeObject`, `LogEntryTextObject`;
  контекст записи — строка JSON; счётчики уровней в индексаторе — `LogLevelCounter`.
- Тесты: `config()->set()` в тесте после загрузки приложения пишется в overlay корневого
  контекста SConcur (`AsyncConfig`) и доживал до следующих тестов процесса.
  `Tests\TestCase::tearDown()` теперь его сбрасывает.

## Бэкенд

Модуль `Logs`, слои по `code-analyse/deptrac-layers.yaml`.

- `Enums`: `LogTypeEnum`, `LaravelLogLevelEnum`, `NginxErrorLevelEnum`,
  `HttpStatusClassEnum`, `LogCursorDirectionEnum`.
- `Entities`: `LogFileObject`, `LogIndexMetaObject`, `LogIndexRecordObject`,
  `LogEntryObject` (общие поля + `fields` типа), `LogEntriesPageObject`,
  `LogLevelStatObject` (остаётся).
- `Parameters`: `FindLogEntriesParameters` (fileIds, levels, from, to, searchQuery,
  cursor, direction, perPage).
- `Domain/Services` для нескольких файлов: `LogFileStream` (поток одного файла),
  `LogStreamsMerger` (k-way merge по фронтам), `LogCursorCodec` (кодирование и строгая
  проверка курсора).
- `Repositories` — только примитивы над `Files`:
  - `LogFileRepository`: `findAll(sources)`, `stat`, `readRange`, `readChunks`;
  - `LogIndexRepository`: `findMeta`, `saveMeta`, `append`, `readRecords`, `truncate`,
    `delete`.
- `Domain/Services`: `Formats/*` (по формату на тип), `LogIndexer`, `LogEntriesReader`,
  `LogSearcher`, `LogFileResolver` (id → файл, только из списка источников).
- `Domain/Actions`: `FindLogFilesAction`, `FindLogEntriesAction` (вместо
  `PaginateLogsAction`), `StreamLogFileAction`, `FindLogErrorStatAction` (переписан),
  `CleanLogsAction`, `IndexLogsAction`.
- Конфиг: `config/logs.php` (новый), `config/cache.php` — ключ
  `'mutex_store' => env('MUTEX_CACHE_STORE', 'sconcur_redis')`, `.env.example` —
  `MUTEX_CACHE_STORE`.
- `Infrastructure/Commands`: `CleanLogsCommand` (`logs:clean`, раз в сутки),
  `IndexLogsCommand` (`logs:index`, раз в минуту).

### Watcher `logErrors`

`FindLogErrorStatAction::handle($since, $until)` — сигнатура та же, `LogErrorsChecker` не
меняется:

1. Файлы типа `laravel` с `mtime > since`.
2. Для каждого `ensureFresh`, затем в `level-<n>.idx` уровней ERROR, CRITICAL, ALERT,
   EMERGENCY бинарный поиск по `unixTime` в `(since, until]`.
3. `count` — сумма. `lastMessage` — первая строка самой поздней записи, один
   `Files::read`.

Как сделано (шаг 5): бинарного поиска по времени здесь нет. Время в файле не обязано
быть монотонным — на реальном `laravel-2026-09-25.log` первые записи помечены 05:35, а
следующие 04:44 (писали процессы с разными часами), и бинарный поиск терял все ошибки
файла. Поэтому `level-<n>.idx` уровней ERROR…EMERGENCY читаются целиком блоками по 50 000
записей (21 байт на запись ошибки — это дёшево) и каждая запись сравнивается с окном
`(since, until]`. Последнее сообщение — у записи с наибольшим временем (при равенстве —
у более нового файла, затем у большего `entryNo`), без контекста. Индекс, занятый другим
индексатором дольше `module-logs.errors.wait_for_lock_sec` (5 с), читается как есть.
Проверено на живых логах: 507 ошибок за 30 дней по файлам — столько же, сколько даёт grep
без трёх записей «из будущего».

Та же немонотонность и в ленте: период `from`/`to` сужает диапазон бинарным поиском, а
каждая запись дополнительно сверяется со временем, так что лишнего в выдаче нет. Запись с
«чужим» временем у самой границы периода может не попасть в выдачу — это ограничение.

### HTTP

Отдельный контроллер на сущность:

- `GET /admin-api/logs/files` → `LogFileController@index`: `id`, `name`, `folder`, `type`,
  `size_bytes`, `modified_at`.
- `GET /admin-api/logs/files/{id}/download` → `LogFileController@download`.
- `POST /admin-api/logs/entries` → `LogEntryController@index` (сделано так вместо
  `GET /admin-api/logs`: 200 `id` по 40 символов и курсор с позицией на каждый файл не
  помещаются в строку запроса — лимит заголовков nginx; старый `GET /admin-api/logs`
  живёт до удаления Mongo-логов):
  - запрос: `files[]` (id, от 1 до `search.max_files`), `levels[]` (`<type>.<level>`),
    `from`, `to`, `search_query` (`min:1`, `max:255`), `cursor`, `direction`,
    `per_page`;
  - ответ: `items[]` (`file_id`, `type`, `entry_no`, `logged_at`, `level`, `message`,
    `text`, `context`, `fields`), `level_counts` (по ключам `<type>.<level>`), `total`,
    `scanned`, `indexing`, `indexed_bytes`, `total_bytes`, `restarted_files`,
    `missing_files`, `older_cursor`, `newer_cursor`.

Неизвестный `id` в `files[]` не ошибка: файл мог уйти с ротацией между списком и
запросом, он возвращается в `missing_files`, остальные читаются. Для скачивания
неизвестный `id` → 404. Путь из запроса не принимается: `id` ищется только среди файлов
источников, path traversal невозможен. После изменений — `make oa-generate`.

### Что удаляется

- `App\Services\Logging\Mongodb\MongodbLogHandler`, канал `mongodb` и его место в `stack`
  (`config/logging.php`).
- `App\Models\Logs\Log`, `CreateLogAction`, `CreateLogParameters`, `PaginateLogsAction`,
  Mongo-реализация `LogRepository`, `LogsPaginationObject`, `LogsPaginationResource`.
- Коннект `mongodb.logs` в `config/database.php`, `MONGO_DATABASE_LOGS` в `.env.example`.
- Новая миграция (`make art c="make:migration ..."`) дропает коллекцию `logs`. Миграция
  `2024_12_22_172459_mongodb_create_logs_table.php` остаётся как есть: она уже
  выполнялась на установках.

### Как сделано (шаг 8)

- Подключение `mongodb.logs` и `MONGO_DATABASE_LOGS` остаются до релиза: их читают старая
  миграция и новая `2026_09_25_072336_mongodb_drop_logs_table`, на чистой установке
  выполняются обе. Удалить после релиза.
- Новая миграция удаляет коллекцию, только если она есть. `down()` создаёт коллекцию и
  индексы старой миграции заново, без документов.
- Стек логирования — только `daily`. `GET /admin-api/logs` удалён; стор старой страницы
  использует `AdminApi.LogsList`, поэтому фронт собирается снова только после шага 9.

## Фронт

`frontend/src/components/pages/logs-viewer/`:

- Слева список файлов по источникам (Laravel, Slogger, nginx): имя, размер, дата.
  Чекбоксы у файлов и у источника («все файлы источника»), кнопка «все файлы». У каждого
  файла — кнопка скачать. Кнопка обновить список.
- Сверху: чипы уровней со счётчиками, сгруппированные по типам выбранных файлов
  (мультивыбор, цвет по уровню), период `from`/`to`, поиск, «просмотрено N из M»,
  «продолжить поиск», индикатор индексации.
- Выбрано больше одного файла — у строк появляется колонка файла. Строка рисуется по
  своему `type`, поэтому в одной выдаче могут идти строки laravel и nginx.
- Таблица по типу:
  - `laravel` — время, уровень, env, первая строка; в раскрытой строке полный текст и
    контекст через `JsonViewer`;
  - `nginx_access` — время, статус, метод, путь, IP, байты; раскрытая — referer, user
    agent, сырая строка;
  - `nginx_error` — время, уровень, сообщение; раскрытая — client, server, request,
    upstream, сырая строка.
- Навигация: «Новее», «Старше», «В начало».
- Место под счётчики, спиннеры и «продолжить поиск» резервируется заранее. Размеры
  шрифтов не задаются.
- Стор переписан под новые типы `api-schema`. Выбранный файл и фильтры в query-строке
  URL. После изменений — `make frontend-npm-build`.

### Как сделано (шаг 9)

- Компоненты: `LogsFiles` (файлы по источникам), `LogsFilters` (период, поиск, чипы
  уровней из `level_counts`), `LogsTable` (строка по своему `type`, колонка файла при
  нескольких файлах). Порядок и цвета уровней — `store/logLevels.ts`.
- При первом открытии выбран самый новый файл первого источника типа `laravel`; если
  таких файлов нет, не выбрано ничего.
- Ответ с `indexing: true` повторяется через секунду, пока показывается прогресс.
- «Continue search» виден, когда поиск принёс меньше страницы (`per_page` 50) и есть
  `older_cursor`; найденное дописывается к странице. «Newer» без новых записей оставляет
  страницу и говорит об этом.
- Скачивание: `fetch` с токеном в blob. Бэкенд отказывает (422) файлу больше
  `module-logs.download.max_bytes` (`LOGS_DOWNLOAD_MAX_BYTES`, 100 МиБ): файл проходит
  через память браузера.

## Тесты

`tests/Modules/Logs`, файлы во временном каталоге:

- форматы: заголовок Laravel с микросекундами и зоной, многострочная запись, контекст,
  мусор до первого заголовка; `combined` с `-` вместо полей; строки error-лога с
  `client`/`request`/`upstream` и без них;
- индексатор: первичный индекс, дозапись, недописанная последняя запись, усечение и
  подмена головы → перестроение, параллельный вызов под мьютексом;
- чтение одного файла: без фильтра, один уровень, несколько уровней, период, курсоры в
  обе стороны, поиск с бюджетом и продолжением;
- несколько файлов: порядок по времени при пересекающихся и непересекающихся файлах,
  совпадения, найденные, но не отданные, приходят на следующей странице без потерь и
  повторов, общий бюджет, фильтр `<type>.<level>` по файлам разных типов, файл,
  ротированный между запросами (`restarted`), испорченный или чужой курсор → 422;
- `FindLogErrorStatAction`: окно, несколько файлов, последнее сообщение;
- `CleanLogsAction`: удаляет только старые `access-*.log` и индексы файлов, которых нет;
- HTTP: неизвестный id → 404, валидация, скачивание.

`LogErrorsCheckerTest` мокает action и не меняется.

## Логи ресивера

Ресивер (`servers/receiver`) пишет в `servers/receiver/storage/logs/Y-m-d.log`, старые
файлы удаляет сам (`LOG_KEEP_DAYS`). Воркеры видят весь проект в `/app`, новых томов не
нужно.

- Формат: `2026-09-25 07:00:57.401 ERROR message` (`formatter.go`), время UTC, уровни slog
  DEBUG, INFO, WARN, ERROR. Ошибка может продолжаться блоком `->stack trace:` …
  `<-end of trace` — запись длится до следующего заголовка, как у Laravel.
- При `LOG_LEVELS=any` — до 50 МБ и 450 тысяч записей в день, почти всё DEBUG. Индекс
  такого файла — около 20 МБ.
- `LogTypeEnum::Receiver = 'receiver'`, `ReceiverLogLevelEnum` (Debug 1, Info 2, Warn 3,
  Error 4), `Formats/ReceiverLogFormat` по образцу `LaravelLogFormat`: сообщение — первая
  строка, полей нет. Регистрация в `LogFormatRegistry`.
- Источник `Receiver` в `config/module-logs.php`: `env('LOGS_RECEIVER_PATH',
  base_path('servers/receiver/storage/logs'))`, `*.log`, без `keep_days`. В `.env.example`
  закомментированный `LOGS_RECEIVER_PATH`.
- Фронт (`store/logLevels.ts`): группа «Receiver», порядок и цвета уровней. Строка рисуется
  как строка Laravel.
- Тесты: `ReceiverLogFormatTest`, ключ `receiver.WARN` в `LogLevelKeysTest`.

### Как сделано (шаг 10)

- Сделано по плану. Сообщение — первая строка после заголовка; stack trace остаётся в
  полном тексте записи.
- Живые логи ресивера (4 файла, 77 МБ, 710 тысяч записей) индексируются за ~2.5 с, индексы
  всех источников — 30 МБ. Счётчики уровней совпадают с grep.

## Watcher `receiverErrors`

Как `logErrors`, только по логам ресивера.

- Logs: подсчёт из `FindLogErrorStatAction` выносится в
  `Domain/Services/Errors/LogErrorStatCounter` (тип файлов + уровни ошибок).
  `FindLogErrorStatAction` — `laravel`, ERROR…EMERGENCY, как сейчас. Новый
  `FindReceiverErrorStatAction` — `receiver`, ERROR, та же сигнатура `handle($since, $until)`.
  Watcher не зависит от enum-ов Logs.
- Watcher: `WatcherTypeEnum::ReceiverErrors = 'receiverErrors'` (cooldown 600),
  `ReceiverErrorsWatcherType` («Errors in receiver logs»), `ReceiverErrorsChecker`, реестр,
  провайдер, маршруты `watchers/receiver-errors` и `incidents/{id}/events/receiver-errors`,
  `ReceiverErrorsWatcherController`, `ReceiverErrorsIncidentEventController`.
- Notification не меняется — работает через реестр. Ресивер на Go не меняется — читает
  только watcher-ы с `trace_match`.
- `.ai/README.md`: связь Watcher → Logs `ReceiverErrorsChecker` → `FindReceiverErrorStatAction`.
- Фронт: `watchersStore.ts` (эндпоинты), `incidentsStore.ts` и `IncidentEvents.vue`
  (колонки как у `logErrors`). Форма строится из описания типа.
- Тесты: чекер, реестр, маппер событий, action на временных файлах.

Открытый вопрос: переиспользовать для `receiverErrors` классы `logErrors` (настройки,
событие, маппер, request, ресурсы — у них одинаковая форма) или завести полный набор
`ReceiverErrors*`. Рекомендация — переиспользовать: новых классов четыре (тип, чекер, два
контроллера) вместо ~14.

## Удаление файлов

Только файлы типа `laravel` (Laravel и Slogger).

- `DELETE /admin-api/logs/files/{id}` → `LogFileController@delete` → `DeleteLogFileAction`:
  под `LogIndexMutex` удаляет файл и каталог его индекса (те же примитивы, что у
  `logs:clean`). Неизвестный `id` → 404, другой тип → 422, мьютекс не дождались → 409.
- Фронт: кнопка удаления с `el-popconfirm` рядом со скачиванием, только у `laravel`; у
  остальных — пустое место той же ширины. После удаления — перечитать файлы, убрать из
  выбора, перезагрузить записи.
- Тесты: HTTP и action.

Открытый вопрос: файл, в который сейчас пишут. Воркеры долгоживущие и держат сегодняшний
файл открытым: после удаления записи до конца дня уходят в удалённый файл. Рекомендация —
запретить удаление самого нового файла каждого `laravel`-источника (422, кнопка неактивна).

## Документация

- `README.md` и `README.ru.md` вместе: хранилище логов (строки про MongoDB), watcher
  `logErrors` (считает по файлам, а не по коллекции), страница логов, nginx-логи и
  `LOGS_NGINX_PATH`, `error.log` без ротации, логи ресивера, watcher `receiverErrors`,
  удаление файлов.

## Порядок работ

1. Мьютекс в `Common`, тесты. Сделано.
2. Форматы, индекс, индексатор, тесты. Сделано.
3. Чтение одного файла, поиск. Сделано.
4. Несколько файлов: потоки, merge, курсор, бюджет. Actions, HTTP, `make oa-generate`. Сделано.
5. `FindLogErrorStatAction` на индексах. Сделано.
6. `logs:index` (раз в минуту), `logs:clean` (раз в сутки). Сделано.
7. nginx: compose, шаблон, entrypoint-скрипт. Сделано.
8. Удаление Mongo-логов, миграция. Сделано.
9. Фронт, `make frontend-npm-build`. Сделано.
10. Логи ресивера: формат, источник, фронт. Сделано.
11. Watcher `receiverErrors`.
12. Удаление файлов `laravel`.
13. README, `make check`.

## За рамками

Regex-поиск, live-tail через WS, удаление файлов кроме `laravel`,
форматы кроме перечисленных (JSON-логи, `main` nginx с дополнительными полями).
