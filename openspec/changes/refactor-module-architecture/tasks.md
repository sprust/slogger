# Tasks

## 1. Архитектурные проверки

- [x] 1.1 Расширить `code-analyse/deptrac-layers.yaml`: Enums, события/исключения, providers, Common и точки сборки. Проверить положительные и отрицательные fixtures в `tests/Architecture`, включая провайдер в корне Infrastructure и enum с доступом к репозиторию.
- [x] 1.2 Создать `code-analyse/deptrac-modules.php` с автоматическим обнаружением модулей и матрицей из design. Проверить отказ для чужих Repositories/Infrastructure, обратной связи Watcher → Notification и неизвестного направления нового модуля; разрешить контракт событий без доступа ко всему чужому Domain.
- [x] 1.3 Добавить проверку структуры модулей по образцу PMS с местными Enums/Tasks/Dto/UseCases. Проверить неизвестный каталог и разрешённое существующее дерево, без требования пустых папок.
- [x] 1.4 Добавить правила PHPStan для чужих Parameters во входных контрактах, обходов через aliases/наследование/unions/generics/вложенные DTO, UseCase → Repository/UseCase и repository → другой repository. Проверить отрицательные fixtures и разрешённое создание чужого Parameters внутри Domain.
- [x] 1.5 Подключить проверки в `makefile` через существующий `make check`, описать новые команды и ограничения в `.ai/README.md`. Проверить, что нарушение каждой категории возвращает ненулевой код; не добавлять baseline для существующих нарушений.

- [x] 1.6 Перенести преобразования в модульные `Transports/*Transport`, включить слой в структуру и оба Deptrac-конфига. Проверить разрешённое преобразование чужих DTO по графу и отказ для чужих Domain/Repositories/Infrastructure, обратного направления, вызова Transport из Entities/Infrastructure.
- [x] 1.7 Перенести применимые к SLogger общие правила и навыки PMS, без дублирования команд; применить их к создаваемым и затронутым файлам. Зафиксировать исключение Entity/Object на входе только Transports и ранее согласованные отличия; проверить ссылки, навыки и соответствие кода.

## 2. Контракты MCP и преобразования

- [x] 2.1 Заменить чужие Watcher DTO/enum в `Mcp/Entities/Bridges` и `FindMcpIncidentsParameters` собственными типами; добавить модульные Transports для инцидентов и событий. Адаптировать `IncidentToolsTest`, сохранив поля, порядок, пагинацию, неизвестный/удалённый watcher и отсутствие событий.
- [x] 2.2 Перевести ответы мостов сервисов, диапазона данных и фасетов на собственные MCP DTO; адаптировать `ServiceAndRangeToolsTest` и тесты фасетов. Проверить отсутствие чужих типов в свойствах и generic-коллекциях.
- [x] 2.3 Добавить локальные представления деталей и рекурсивных данных трейса; убрать `TraceDataResource` из `GetTraceDataTool`. В `TraceToolsTest` и HTTP/MCP parity test проверить значения, null, пустые children, порядок ключей и строки без обрезки.
- [x] 2.4 Перевести дерево и результаты поиска дерева на локальные DTO/enum через модульные Transports; адаптировать тесты tools и bridges. Проверить cursor, children_count, building/failed/canceled и отсутствие повторного построения готового дерева.
- [x] 2.5 Перевести результаты поиска, агрегации и сравнения групп на локальные DTO; отделить локальный результат `McpTraceDataFilterParser` от Parameters Trace. Адаптировать `TraceQueryToolsTest`, `TraceGroupToolsTest` и тесты parser/bridges; проверить прежние фильтры, границы периода и ошибки.
- [x] 2.6 Перевести мост поиска логов и его ошибки на локальные представления; чтение Setting из `McpToolTraceScopeReader` перенести в собственный Domain. Проверить `SearchSloggerLogsToolTest` и период MCP, обновить раздел MCP и границы DTO в `.ai/README.md` по реализованным типам.

## 3. UseCases и межмодульные сценарии

- [x] 3.1 Ввести UseCases Watcher для бизнес-endpoints, перенести проверку типа перед чтением/обновлением из `AbstractWatcherTypeController`, определение пользователя закрытия инцидента — из контроллера в Domain. Проверить HTTP route guards, статусы ошибок и `CloseIncidentActionTest` после адаптации.
- [x] 3.2 Убрать вызов Trace из `CheckWatchersTask`, получая контекст через собственный Domain Watcher. Проверить `CheckWatchersTaskTest`: один запрос размера буфера за проход, независимость проверок от брокера и сохранение поведения при недоступном MongoDB.
- [x] 3.3 Создать локальные данные и UseCase Notification для события Watcher; перенести чтение watcher/последнего события из listener, сохранив snapshot инцидента. Проверить тесты listener, `EnqueueNotificationsActionTest` и `IncidentMessageFactoryTest`, включая отсутствие watcher, отсутствие события и сбой постановки.
- [x] 3.4 Ввести UseCases каналов и отправки Notification, перенести проверку типа из контроллеров и получение данных из job в Domain. Проверить HTTP ответы, отправку тестового сообщения и `SendNotificationJobTest`: permanent failure, retry-after, backoff и уже отправленное уведомление.
- [x] 3.5 Ввести UseCases Auth/User/Service; убрать вызовы User из Auth controllers/middleware через собственные Actions. Проверить HTTP тесты User/Service, login/logout/me и `AuthMiddlewareTerminateTest`, сохранив lifetime токена и обновление активности.
- [x] 3.6 Ввести UseCases Cleaner/Setting/Dashboard; чтение Setting из Cleaner command/job перенести в Domain. Проверить `ClearTracesActionTest`, `SettingAdminHttpTest` и тесты Dashboard: сохранение очистки, ограничений settings и отсутствующего кэша статистики.
- [x] 3.7 Ввести UseCases HTTP-операций Trace/Logs; выделить атомарные Actions из затронутых составных сценариев без изменения SQL и пакетной загрузки. Проверить тесты Trace/Logs, включая фильтры, дерево, индексацию и файловую очистку; Model/Builder не возвращаются из публичных методов репозиториев.
- [x] 3.8 Ввести UseCases управления MCP и Tools; оставить JSON-RPC разбор/маршрутизацию в протокольной Infrastructure. Проверить MCP admin/endpoint tests и ответ ToolLinks; убедиться, что бизнес-endpoints вызывают один местный UseCase через DI.
- [x] 3.9 Завершить очистку межмодульных типов в затронутых модулях, обновить матрицу связей, правила Actions/UseCases и репозиториев в `.ai/README.md`. Проверить отсутствие ActionResolver, proxy, чужих DTO в Entities/Parameters и прямого доступа UseCases к репозиториям/другим UseCases.

## 4. Dispatcher и маршруты доставки

- [x] 4.1 Добавить `Common/Domain/Services/DispatcherInterface.php` и реализацию в `Common/Infrastructure/Services`, связывание в register Common provider. Добавить тесты event/report: исходный payload/exception, одна доставка, распространение исключения listener, сохранение фильтрации ожидаемых ошибок.
- [x] 4.2 Реализовать идемпотентную регистрацию jobs и отказ для незарегистрированного класса через прямую запись в журнал. Проверить отсутствие отправки, повторной регистрации, рекурсивного report и сериализации payload/секретов в диагностике.
- [x] 4.3 Реализовать проверку маршрутов по `queue.*` и `sconcur.queue.rabbitmq.*`, включая приоритет job, default queue и sync. Проверить разрешённые очереди при выключенных consumers, неизвестные connection/queue, sync без RabbitMQ и проброс ошибки брокера без retry dispatcher.
- [x] 4.4 Добавить проверки сохранения delay/after-commit/очереди и изоляции параллельных dispatch. Проверить два одновременных вызова с разными payload/маршрутами и отсутствие состояния запроса в singleton.
- [x] 4.5 Перевести события Trace/Watcher/Notification, три dispatch-listeners и репортинг Dashboard/MCP на интерфейс. Адаптировать тесты дерева, уведомлений и MCP; сохранить запись до события, немедленный broadcast и защиту успешного дерева при сбое трансляции.
- [x] 4.6 Описать методы dispatcher, источник разрешённых маршрутов, sync и поведение отказов в `.ai/README.md`; сверить описание с тестами и убедиться, что Horizon/PMS reporter не добавлены в зависимости.

## 5. Регистрация модулей и расписание

- [x] 5.1 Расширить `BaseServiceProvider` для регистрации собственных jobs/listeners, перенести bindings в register, сохранив существующие lifetimes. Проверить загрузку приложения, разрешение dispatcher и отсутствие static-кэшей контекста запроса.
- [x] 5.2 Зарегистрировать пять application jobs в Trace/Notification/Cleaner/Dashboard, перенести mappings listeners из глобального EventServiceProvider в модульные providers. Проверить полный набор jobs и одну обработку каждого события после boot, без глобального перехвата jobs сторонних пакетов.
- [x] 5.3 Удалить регистрацию `ClearTracesCommand` из Logs и прямую сборку Notification-сервиса через Watcher registry. Проверить доступность команды Cleaner и отсутствие запрещённых межмодульных зависимостей providers.
- [x] 5.4 Перевести два scheduled job запуска в `app/Console/Kernel.php` на callbacks общего dispatcher. Проверить частоту, идентичность jobs/очередей и единственную отправку; команды и SConcur task pool сохраняют прежнее расписание.
- [x] 5.5 Обновить в `.ai/README.md` правила регистрации и отправки jobs вместо прежнего требования helper dispatch. Проверить тестом десериализацию payload всех пяти jobs до рефакторинга без переименования FQCN/полей.

## 6. Доменные ошибки и HTTP

- [x] 6.1 Добавить общий контракт/реестр exception managers в `Common/Infrastructure/Exceptions` и подключение из `app/Exceptions/Handler.php`/providers. Проверить fallback для неизвестной ошибки и отсутствие дублирующего репортинга.
- [x] 6.2 Перенести HTTP mapping ошибок Watcher/Notification/User/Service/Setting из контроллеров в менеджеры, при необходимости добавить контекстные доменные ошибки. Проверить прежние статусы, тела и сообщения ошибок для отсутствия объекта, несовпадения типа и неверного значения.
- [x] 6.3 Перенести mapping Trace/Logs/Dashboard/Auth и управления MCP с сохранением 401/404/409/422/503; протокольные ошибки MCP не направлять в HTTP-manager. Проверить HTTP, JSON-RPC, tool `isError` и `query_failed`, а также ошибки streamed responses, если затронут их путь.
- [x] 6.4 Описать правила доменных исключений и транспортного отображения в `.ai/README.md`; проверить, что Domain не зависит от HTTP responses и принудительный PMS HTTP 400 не введён.

## 7. Итоговая интеграция

- [x] 7.1 Выполнить полный набор архитектурных проверок и убедиться в отсутствии нарушений/временных исключений; сверить правила с DTO, providers и всеми текущими модулями, а не только fixtures.
- [x] 7.2 Запустить регрессии HTTP/MCP/WebSocket, уведомлений, очистки и watchers из `tests/Modules`; подтвердить неизменность контрактов и независимость watchers от брокера. Проверить landing на необходимость обновления: его тексты и переиспользуемые представления должны оставаться актуальными.
- [x] 7.3 Выполнить `make oa-generate`, проверить diff схем: изменения внешних полей, required/nullability и статусов не допускаются. При изменении сгенерированного TypeScript или frontend source выполнить `make frontend-npm-build`; иначе зафиксировать отсутствие основания для сборки.
- [x] 7.4 Выполнить `make check` после завершения изменений и генерации. Зафиксировать успешный результат, проверить `git diff --check`, актуальность `.ai/README.md` и отсутствие изменений схемы БД/Go receiver; остановить сервисы, запущенные только для проверки.

## 8. ModulesConfig и жизненный цикл providers из PMS

- [x] 8.1 Перенести BaseServiceProvider в Infrastructure/Providers, добавить hooks и обязательные модульные регистраторы; сохранить DI lifetime, обновить структуру и Deptrac.
- [x] 8.2 Перевести providers на hooks, собственные команды/jobs/listeners/exception manager и setSchedule; собирать расписание через ModulesConfig, сохранить параметры всех пяти запусков.
- [x] 8.3 Проверить lifecycle, команды, расписание, изоляцию регистраций разных контейнеров и архитектурные ограничения; обновить документацию, выполнить make check и ревью, остановить проверочные сервисы.

## Итоговая проверка

- `make check`: 901 тест, 3051 assertions; PHPStan, оба Deptrac-конфига,
  структура модулей и полный единый formatter PMS прошли без ошибок.
- Все 97 правил CS Fixer совпадают с PMS; адаптированы пути. Повторный dry-run:
  0 исправлений из 1087 файлов; обёртка diff сохраняет exit code 0/8.
- `make oa-generate`: схемы и generated TypeScript совпадают с исходными.
  Frontend source не изменён, оснований для `make frontend-npm-build` нет.
- Проверены тексты landing и MCP-примеры: описанный внешний контракт сохраняется.
- `openspec validate refactor-module-architecture --strict` и `git diff --check` пройдены.
- Ревью `code-reviewer`: PASSED после исправления HTTP-ошибки Logs, защиты
  структуры каталогов, PHPDoc и удаления неиспользуемого преобразования.
- Финальное ревью providers: PASSED; подтверждены 5 jobs, 6 команд, 5 listeners,
  все пять расписаний, console-only команды и независимость двух контейнеров.
- PHP-FPM, MySQL, Redis, RabbitMQ, MongoDB и ClickHouse, запущенные для проверок,
  остановлены. Схема БД и Go receiver не менялись.
