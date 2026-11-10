# Design

## Context

Мотивация и границы изменения — в [proposal.md](proposal.md).

До начала изменения в SLogger общий `code-analyse/deptrac-layers.yaml` не различал модули, не собирал Enums и файлы в корне Infrastructure. Провайдеры использовали `Common/Infrastructure/BaseServiceProvider.php`, где все `getContracts()` регистрировались singleton. События Trace и Notification подключались в `app/Providers/EventServiceProvider.php`; расписание находилось в `app/Console/Kernel.php`.

Наблюдаемые места рефакторинга:

- `Mcp/Entities/Bridges/McpIncidentObject`, `McpTraceFacetListObject`, `McpTraceTreeNodeObject` содержат Entities других модулей; `McpTraceTreeObject` и `Mcp/Parameters/FindMcpIncidentsParameters` используют чужие enum.
- Часть `Mcp/Domain/Actions/Bridges` возвращает объекты Trace, Service, Watcher и Logs непосредственно. `McpTraceDataFilterParser` создаёт Parameters Trace; `GetTraceDataTool` сериализует чужой HTTP-resource.
- `Notification/Infrastructure/Listeners/EnqueueNotificationsListener` вызывает Actions Watcher. `Watcher/Infrastructure/Tasks/CheckWatchersTask` получает размер буфера через Trace. `Auth/Infrastructure` напрямую вызывает User. Cleaner и MCP читают Setting из Infrastructure.
- `AbstractWatcherTypeController::updateWatcher` и `AbstractChannelTypeController` совмещают проверку типа с изменением. Составные Actions, например `Trace/Domain/Actions/Queries/FindTracesAction`, оркестрируют несколько хранилищ.
- `LogsServiceProvider` повторно регистрирует команду Cleaner, уже зарегистрированную в `CleanerServiceProvider`.
- Трейсинг, RabbitMQ и task pool работают в SConcur. `CheckWatchersTask` сознательно не зависит от брокера. `TraceTreeStateBroadcast` использует немедленную трансляцию с защитой результата построения от сбоя отправки.

Существующие спецификации описывают пользовательское поведение. Они остаются регрессионными ограничениями. Новые capabilities описывают диагностику архитектуры при проверке проекта и поведение dispatcher, включая отказ для незарегистрированной job.

## Goals / Non-Goals

**Goals:**

- Завершить изменение с зелёными проверками всей кодовой базы, без baseline новых нарушений и без исключения MCP bridges из правил DTO.
- Сделать модульные границы явными и проверяемыми, включая типы, которые Deptrac сам не различает по месту использования.
- Сохранить форматы HTTP, MCP, WebSocket, очередных сообщений и параметры хранения.

**Non-Goals:**

- Внедрение ActionResolver, service locator для бизнес-вызовов, proxy, новых автоматических повторов или Horizon.
- Перевод Domain на полный отказ от всех существующих framework utility-классов; здесь устраняются согласованные нарушения границ и вводится dispatcher-контракт.
- Новый универсальный слой Adapter, интерфейс для каждого репозитория, универсальный CRUD или массовое деление небольших репозиториев на read/write.
- Замена хранилищ, изменение Go receiver, отказ от физического удаления, перенос SConcur tasks в jobs.

## Decisions

### 1. Проверки проекта (уточнение пользователя 2026-11-10)

Подключаются перенесённые пользователем code-analyse/deptrac/deptrac-layers.yaml,
code-analyse/deptrac/deptrac-modules.php, code-analyse/phpstan/phpstan.neon,
code-analyse/cs-fixer/.php-cs-fixer.dist.php и standalone module-structure CLI.
Алгоритмы/матрицы и флаги проверок не переделываются. Адаптация ограничена
путями, autoload, списком существующих shared modules и unmatched ignore patterns.

Исходная матрица различает Adapter/Domain/Entities/Parameters/Transports/Repositories/
Infrastructure/Models. Модульная матрица исключает Common, разрешает Domain доступ
к чужому Domain/Objects и запрещает чужие Repositories/Infrastructure по матрице зависимостей.
Providers в корне Infrastructure и Enums не получают придуманных отдельных collectors.
NoMultilineArrayKeyRule — единственное дополнительное PHPStan-правило. Удалённые
InputParametersRule/DomainDependencyRule и line-length check не восстанавливаются.
Ранее согласованные бизнес-ограничения проверяются ревью; скопированные инструменты
не объявляются обеспечивающими больше, чем действительно проверяют.

PHPStan охватывает app/tests/seeders с checkUninitializedProperties и
 treatPhpDocTypesAsCertain:false. Найденные ошибки типов исправляются в коде,
а не маскируются baseline или новыми глобальными исключениями.

### 2. Собственные DTO и модульные Transports

В каждом принимающем модуле создаём собственные readonly Entities/Parameters. Их вложенные поля, коллекции и enum также локальные либо общие, действительно универсальные типы. Не переносим бизнес-DTO в Common для обхода правила. Не заменяем их `mixed`, неструктурированными массивами, наследованием или псевдонимами.

Преобразования располагаются в `Transports` принимающего модуля: классы `*ObjectTransport` / `*ParametersTransport`, методы `from...` / `to...`. Они получают типизированные исходные Entities/Parameters/Enums и возвращают локальные DTO либо Parameters вызываемого модуля по разрешённому направлению. Вызываются из Actions/UseCases через обычный DI. Чтение данных, вызовы чужих Actions/Services и оркестрация остаются в Domain. Отдельный каталог `Domain/Services/Mappers` не вводим.

Исключение из запрета Entity/Object на входе публичного метода относится только к преобразователям в `Transports`. Остальные публичные Domain-методы принимают примитивы, enum или собственные Parameters. Чужой Parameters создаётся внутри Domain либо через свой Transport непосредственно перед вызовом чужого Action; не проходит через контроллер или вход своего UseCase.

`Transports` учитывается проверкой структуры и слоевым конфигом Deptrac. Его собственные зависимости — Entities, Parameters, Enums, Repositories и другие Transports; чужие данные — только по матрице направлений. Чужие Repositories/Infrastructure/Domain недоступны. Domain, UseCases и Repositories могут использовать свои Transports; Entities/Parameters/Enums/Infrastructure — нет. Common проверяется без обратных бизнес-связей. Межмодульный конфиг не собирает Transports: соблюдение направлений проверяется при ревью.

Для MCP покрываем не только четыре существующих bridge DTO, но все ответы мостов: сервисы, инциденты и события, фасеты, детали и данные трейса, дерево, группы, сравнения, логи. `McpTraceDataFilterParser` возвращает местные параметры; преобразование к фильтру Trace выполняется своим Transport, вызываемым из Domain перед запросом. Собственные enum преобразуются явно с сохранением значений внешнего протокола.

`GetTraceDataTool` получает собственное представление дерева данных и сериализует его средствами MCP. Нельзя вызывать `TraceDataResource` или переместить этот HTTP-resource в Common. Рекурсивный Transport сохраняет `key`, `value`, `children`, `filter_key`, `can_be_filtered`, порядок, null, пустые коллекции и типы. Сравнение результата с admin API закрепляется тестом.

Notification получает собственное представление данных для сообщения вместо хранения объектов Watcher в своих DTO. Event listener может принять событие Watcher как интеграционный контракт, но передаёт в свой сценарий локальные параметры, сохраняя snapshot события там, где повторное чтение изменило бы смысл уведомления. Логику получения последнего события и watcher переносим в Domain Notification. Регрессии сохраняют поведение удалённого watcher, отсутствующего события и ошибки постановки.

Альтернатива с исключениями для bridges отклонена пользователем. Цена дублирования принята; полнота преобразований проверяется тестами контрактов.

### 3. UseCases с обычным DI

Для HTTP бизнес-операций вводим `Domain/UseCases`, один UseCase на операцию endpoint. Вызов может быть простым делегированием существующему Action; составные проверки/изменения собираются в одном сценарии. Сохраняем один публичный `handle`. UseCase получает Actions и Services через конструктор; Action выполняет операции с репозиторием.

Области перехода:

- Watcher: создание, чтение настроек и обновление с проверкой типа; закрытие инцидента с определением пользователя через Auth внутри Domain; подготовка контекста прохода из Trace через собственный Action.
- Notification: создание/изменение каналов с проверкой типа; подготовка уведомления по событию Watcher; отправка с поиском данных, сохранением текущих permanent/retry-after исходов.
- Auth: login/logout/me и интеграция с User; middleware сохраняет свою транспортную роль, работу с токеном делегирует собственному Domain.
- Mcp: управление подключениями/настройками через UseCases; tools остаются протокольными адаптерами и вызывают свои Actions/UseCases. Протокольный endpoint сохраняет разбор JSON-RPC, валидацию заголовков и маршрутизацию в Infrastructure — это транспорт, не бизнес-оркестрация.
- Cleaner, Dashboard, Logs, Trace, User, Service, Setting: существующие HTTP операции переводятся на собственные UseCases; чтение Setting из Cleaner/MCP Infrastructure перемещается в свой Domain.
- Tools: `ToolLinksController` получает данные через собственный UseCase; построение списка ссылок отделяется от транспорта с сохранением ответа.

Составные Actions разделяем на оркестрацию и атомарные операции там, где они обслуживают переносимые сценарии; общий бизнес-фрагмент извлекается в Action/Service, а не во второй UseCase. Для общих с MCP операций Trace сохраняет доменный API через Actions: чужие модули не начинают вызывать его HTTP-ориентированные UseCases.

Внешние HTTP-клиенты и чтение конфигурации не переносятся в UseCase как часть бизнес-оркестрации. Если перемещение сценария требует такого вызова, оставляем интерфейс в Domain и адаптер в Infrastructure. Не затеваем отдельную миграцию всех существующих технических сервисов.

### 4. Dispatcher и SConcur

Добавляем `Common/Domain/Services/DispatcherInterface.php` с четырьмя методами:

```php
public function event(object $event): void;
public function dispatch(ShouldQueue $job): void;
public function report(Throwable $exception): void;
public function registerJobs(array $jobClasses): void;
```

`Common/Infrastructure/Services/Dispatcher.php` связывается с интерфейсом в `CommonServiceProvider`. Реализация использует существующие Laravel event/bus/exception contracts через DI. Отправка выполняется до возврата из метода, не откладывается на деструктор временного объекта. Domain публикует события; создание Infrastructure jobs остаётся в listeners.

Реестр классов наполняется модульными провайдерами при boot и дедуплицируется. Во время запросов/задач реестр не изменяется. Singleton dispatcher не хранит текущие параметры вызова. Провайдер не использует static-кэши dispatcher.

Проверка маршрутов оформляется отдельным инфраструктурным сервисом, получающим готовую конфигурацию:

- effective connection: явно заданный job connection либо `queue.default`;
- effective queue: явно заданная job queue, затем `queue.connections.<connection>.queue`, затем `default`;
- для `sconcur_rabbitmq` разрешены connection из `sconcur.queue.rabbitmq.connection` и очереди из `sconcur.queue.rabbitmq.queues`;
- источник разрешений — объявленная топология, а не `sconcur.master.groups`, поскольку группы могут быть выключены; текущая доступность брокера не проверяется;
- `sync` допускается без асинхронной топологии; job всё равно должна быть зарегистрирована;
- остальные асинхронные connection не открываются автоматически наличием записи в шаблонном `queue.php`: неподдерживаемый маршрут отклоняется и диагностируется.

Неизвестный класс или маршрут записывает ошибку через LoggerInterface и прекращает отправку. Payload и секреты не попадают в сообщение. Ошибка самого транспорта пробрасывается; retry остаётся ответственностью jobs/очереди. `report()` использует ReportableException: Handler передаёт оригинал Laravel, обходя только модульный dontReport. Дополнительные подсистемы отчётов не добавляются.

Все пять текущих application jobs регистрируются: построение/удаление кэша Trace, отправка Notification, очистка Cleaner, обновление статистики Dashboard. Вызовы событий в Trace/Watcher/Notification и репортинг в Dashboard/MCP переводятся на интерфейс. Служебные jobs библиотек, включая отправку трейсов пакетом slogger, не перехватываются глобальной заменой Laravel bus.

`TraceTreeStateBroadcast` отправляется через `event()`, сохраняя ShouldBroadcastNow/ShouldRescue. Это событие, не application job; реестр jobs к нему не применяется.

`Schedule::job` обходит прикладной dispatcher. Поэтому два существующих запуска jobs заменяются плановыми callbacks, вызывающими dispatcher с теми же jobs и периодами; именование/ограничения расписания сохраняются. Команды индексации, очистки логов и удаления токенов остаются командами. `CheckWatchersTask` и `RefreshTraceDataPathTypesTask` остаются в task pool.

### 5. Модульные providers и обработчики ошибок

В `BaseServiceProvider` добавляем явные точки регистрации собственных jobs, listeners и exception managers. Регистрацию DI переносим в register, а подключение listeners/команд — в boot; обеспечиваем доступность dispatcher после завершения register всех providers. Убираем собственные mappings модулей из глобального EventServiceProvider и повторную регистрацию ClearTracesCommand из Logs.

`NotificationServiceProvider` больше не собирает сервис с прямой зависимостью на Watcher registry: эта интеграция принадлежит Domain Notification и внедряется обычным DI. Сборка приложения не становится исключением для произвольных связей бизнес-модулей.

Общие интерфейс/реестр exception managers живут в `Common/Infrastructure/Exceptions`, реализации — в `Infrastructure/Exceptions` модулей. `app/Exceptions/Handler.php` делегирует им обработку. Domain содержит только ошибки и контекст, без HTTP-кодов/response builders. Сохраняем существующие 401/404/409/422/503, тела и сообщения; не вводим принудительный 400. Учитываем контекст операции там, где один тип ошибки раньше отображался по-разному. MCP protocol/tool errors обрабатываются существующим протокольным механизмом.

Обновление структуры выполняется отдельным завершающим этапом:

- `ModulesConfig::getProviders()` — единственный список модульных providers, Common первый. Его используют `config/app.php` и цикл `Console/Kernel::schedule`.
- Базовый класс размещается в `Common/Infrastructure/Providers/BaseServiceProvider`. `register` устанавливает существующие singleton-привязки SLogger через `getContracts`, затем вызывает `onRegister` для фабрик. `boot` вызывает `onBoot` и регистрирует listeners, команды (только в console), jobs и один exception manager.
- Каждый модуль явно объявляет `setSchedule`, `getCommands`, `getJobs`, `getListeners` и `getExceptionManagerClass`; отсутствие регистраций выражается пустым списком либо null. Модульные providers больше не переопределяют Laravel `register`/`boot`.
- Расписание jobs принадлежит Cleaner и Dashboard, команд — User и Logs. Частота, имена и `withoutOverlapping` сохраняются. Плановые callbacks по-прежнему отправляют jobs через DispatcherInterface.
- Сохраняется расширение SLogger `getContracts`, чтобы не менять DI lifetime существующих сервисов. Не переносятся AppContext, static-кеши контейнера, ActionResolver/proxy. `final` не добавляется согласно локальным правилам.
- Deptrac учитывает базовый provider в новом каталоге; перенос не освобождает providers от запрета чужих бизнес-регистраций.

### 6. Репозитории и хранение

Сохраняем слой Repositories и его DTO-маппинг. Repo возвращает примитив/DTO, не Model или Builder. Несколько хранилищ координирует Domain. Уже существующий `ClickhouseTraceFilterBuilder` остаётся единым построителем фильтров; разбиение классов не меняет SQL, использование FINAL, пагинацию и ограничения пакетных выборок.

Для переноса составного сценария выделяем атомарные Query/Mutation Actions поверх существующих методов репозитория. Новый BuilderMaker либо разделение read/write вводятся только при выявленном дублировании запросов в затронутом сценарии; отдельного массового переименования Repository/RawRepository нет. Физическое удаление и очистка партиций сохраняются.

## Risks / Trade-offs

- Дублирование DTO расходится по полям → проверка Transports и паритет HTTP/MCP, включая рекурсивные данные, enum, время и null.
- Новая проверка выдаёт много нарушений → мигрировать по модулям, завершить без baseline; отрицательные примеры не входят в production scan.
- Провайдеры случайно регистрируют listener дважды → интеграционный тест загрузки приложения и одной публикации события.
- Allowlist подавляет существующий job → тест полного списка application jobs, маршрутов default/trace-tree/traces-clearing и плановых запусков. Ошибки конфигурации видны в журнале; публикация не зависит от наличия активного worker.
- Изменение порядка событий/записи ломает восстановление → сохранить запись уведомления до публикации и состояния дерева до broadcast, проверить сбой брокера.
- Изменение обработки ошибок меняет API → регрессионные тесты статуса, тела, `isError`/JSON-RPC и reportability, включая отсутствие двойного отчёта.
- Смена DI lifetime вызывает утечку между Fiber → сохранять stateless зависимости, добавить тест параллельных dispatch с разными параметрами.
- Миграция общего сценария добавляет запросы или копирует огромный payload несколько раз → Transport строит ровно одно локальное представление, не сериализует через JSON; тесты сохраняют ленивую/пакетную загрузку деревьев и счётчики запросов в затронутых ветках.

## Migration Plan

1. Добавить архитектурные проверки и тестовые примеры; провести перенос DTO и вызовов до зелёного результата.
2. Перевести бизнес-endpoints/составные адаптеры на UseCases и внедрить dispatcher вместе с регистрацией всех application jobs.
3. Перенести listeners и exception mapping, обновить инструкции проекта, прогнать проверки и генератор OpenAPI.
4. При внедрении перезапустить долгоживущие SConcur workers обычной процедурой деплоя, чтобы они загрузили новые bindings/listeners. Это требование будущего деплоя, не действие при подготовке change.
5. Миграции данных не нужны. Сохранить FQCN и сериализованные поля jobs, чтобы очередь пережила обновление и откат к предыдущему релизу. Не очищать очереди и хранилища ради рефакторинга.

### Правила разработки

Переносим только применимые к SLogger общие инструкции и тематические гайды, с явной адаптацией к существующим путям, командам, тестовой инфраструктуре и хранилищам SLogger. Сохраняем один навык на операцию: текущие официальные OpenSpec skills остаются, используется существующий SLogger review. Правила применяются к новым и затронутым файлам, включая вертикальные массивы/именованные аргументы, полные имена зависимостей, readonly DTO, локальные enum, типизированные контракты и правила тестирования. Согласованные отличия сохраняются: обычный DI, физическое удаление, Dispatcher с SConcur, прежние HTTP-статусы.

## Исправление по повторной сверке

Самодельный map(Response|HttpException|null) заменяется исходной моделью Manager:
ExceptionManagerInterface::isReportable/makeJsonHttpResponse, BaseExceptionManager,
ExceptionManagers и JsonHttpExceptionResponseObject. Распаковка StreamedResponseException
происходит перед распознаванием. Handler отдельно фильтрует report и рендерит JSON.
SLogger сохраняет статусы 401/404/409/422/503 и поле error для Dashboard: DTO ответа
получает только совместимый флаг isError (по умолчанию false), поскольку
унификация этого поля сломает существующий HTTP-контракт. Дополнительные переводы, LocaleRules
и ErrorReport-модуль не переносятся. Явный report оборачивается в ReportableException и обходит только модульный dontReport. Обычный report сохраняет фильтрацию.

Обход всего собственного PHP-кода включает app, tests, packages, routes, config,
database/seeders, bootstrap и code-analyse. Существующие database/migrations
сохраняют прежнее оформление и исключены из массового форматирования/line check. Вертикально раскрываются массивы, длинные
сигнатуры/вызовы/условия и fluent chains; литералы сохраняют значения. Generated
artifacts и отрицательные parser fixtures сохраняют свои контракты. Сравнение AST
до/после отделяет форматирование от изменений обработки исключений.

Результаты повторной сверки и оставшиеся несоответствия входных Parameters,
legacy Domain-сигнатур и анализа/стиля бизнес-тестов зафиксированы в
[.ai/architecture-notes.md](../../../.ai/architecture-notes.md). Их устранение требует самостоятельного
функционального рефакторинга; результат этого прохода не объявляет их исправленными.

## Базовые исключения модулей (уточнение 2026-11-10)

Базовые типы — Auth/Domain/Exceptions/AbstractAuthException, Common/Domain/Exceptions/
AbstractCommonException и модульные ExceptionManager. Каждый модуль с Domain Exceptions
получает собственный abstract class Abstract<Module>Exception extends Exception;
конкретные ошибки наследуют его. Общего родителя поверх Exception не добавляем.
Менеджер распознаёт модуль через instanceof базового класса, dontReport остаётся
списком конкретных ожидаемых ошибок. Новая ошибка модуля автоматически репортится;
неизвестный тип получает безопасное общее сообщение.

Существующие статусы/сообщения сохраняются. Внутренние LogFileNotFoundException,
McpServerNameInvalidException и MutexLockTimeoutException остаются reportable и
получают HTTP 500 с Server Error без раскрытия путей/настроек/ключей блокировки.
Common регистрирует свой менеджер через getExceptionManagerClass. Протокольные
исключения MCP остаются в Infrastructure с существующей обработкой JSON-RPC/tools.
Модули без доменных исключений не получают неиспользуемые пустые классы.

Уточнение Auth: текст ответа задаёт само исключение, менеджер возвращает getMessage;
AuthenticationFailedException содержит Authentication failed. Сообщения наследников
Auth должны быть пригодны для пользователя. Ошибка callback after-lock в MutexManager
оборачивается в RuntimeException с исходным previous после освобождения блокировки.

## Уточнение оформления по пользовательскому diff

Образцы — коммит f818eb56 и правила оформления проекта. Короткие helper-вызовы, условия и
сигнатуры fn остаются компактными; сложные ветки/аргументы раскрываются по смыслу.
Вложенность сама по себе не требует переноса; Resource с результатом UseCase
сохраняет отдельную строку handle(). Непустые списки регистраций, dontReport,
проекции БД и возвращаемые кортежи оформляются вертикально. Разные логические
шаги отделены пустыми строками. Исходные fixers не изменяются.

Пользовательская замена messageField на isError сохраняет HTTP-поля error/message;
тесты проверяют boolean DTO и прежние JSON-ответы отдельно.

## Правила оформления

Оформление определяется скопированными без пересказа разделами `.ai/code-style.md`
и прямыми уточнениями пользователя из `.ai/rules.md`. Именованные аргументы
располагаются вертикально с trailing comma и в порядке параметров сигнатуры;
для assert-вызовов trailing comma не ставится. У методов с несколькими аргументами
или необязательными параметрами используются именованные аргументы. Простому
обязательному единственному аргументу дополнительное имя не требуется.
Заголовки foreach остаются однострочными: получение iterable вынесено перед циклом.

Конструкторы Object/Parameters используют именованные аргументы и при единственном
обязательном поле, как предписывает отдельное правило DTO.
