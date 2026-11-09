# Tasks

## 1. Архитектурные проверки

- [ ] 1.1 Расширить `code-analyse/deptrac-layers.yaml`: Enums, события/исключения, providers, Common и точки сборки. Проверить положительные и отрицательные fixtures в `tests/Architecture`, включая провайдер в корне Infrastructure и enum с доступом к репозиторию.
- [ ] 1.2 Создать `code-analyse/deptrac-modules.php` с автоматическим обнаружением модулей и матрицей из design. Проверить отказ для чужих Repositories/Infrastructure, обратной связи Watcher → Notification и неизвестного направления нового модуля; разрешить контракт событий без доступа ко всему чужому Domain.
- [ ] 1.3 Добавить проверку структуры модулей по образцу PMS с местными Enums/Tasks/Dto/UseCases. Проверить неизвестный каталог и разрешённое существующее дерево, без требования пустых папок.
- [ ] 1.4 Добавить правила PHPStan для чужих Parameters во входных контрактах, обходов через aliases/наследование/unions/generics/вложенные DTO, UseCase → Repository/UseCase и repository → другой repository. Проверить отрицательные fixtures и разрешённое создание чужого Parameters внутри Domain.
- [ ] 1.5 Подключить проверки в `makefile` через существующий `make check`, описать новые команды и ограничения в `.ai/README.md`. Проверить, что нарушение каждой категории возвращает ненулевой код; не добавлять baseline для существующих нарушений.

## 2. Контракты MCP и преобразования

- [ ] 2.1 Заменить чужие Watcher DTO/enum в `Mcp/Entities/Bridges` и `FindMcpIncidentsParameters` собственными типами; добавить Domain mappers для инцидентов и событий. Адаптировать `IncidentToolsTest`, сохранив поля, порядок, пагинацию, неизвестный/удалённый watcher и отсутствие событий.
- [ ] 2.2 Перевести ответы мостов сервисов, диапазона данных и фасетов на собственные MCP DTO; адаптировать `ServiceAndRangeToolsTest` и тесты фасетов. Проверить отсутствие чужих типов в свойствах и generic-коллекциях.
- [ ] 2.3 Добавить локальные представления деталей и рекурсивных данных трейса; убрать `TraceDataResource` из `GetTraceDataTool`. В `TraceToolsTest` и HTTP/MCP parity test проверить значения, null, пустые children, порядок ключей и строки без обрезки.
- [ ] 2.4 Перевести дерево и результаты поиска дерева на локальные DTO/enum с Domain mappers; адаптировать тесты tools и bridges. Проверить cursor, children_count, building/failed/canceled и отсутствие повторного построения готового дерева.
- [ ] 2.5 Перевести результаты поиска, агрегации и сравнения групп на локальные DTO; отделить локальный результат `McpTraceDataFilterParser` от Parameters Trace. Адаптировать `TraceQueryToolsTest`, `TraceGroupToolsTest` и тесты parser/bridges; проверить прежние фильтры, границы периода и ошибки.
- [ ] 2.6 Перевести мост поиска логов и его ошибки на локальные представления; чтение Setting из `McpToolTraceScopeReader` перенести в собственный Domain. Проверить `SearchSloggerLogsToolTest` и период MCP, обновить раздел MCP и границы DTO в `.ai/README.md` по реализованным типам.

## 3. UseCases и межмодульные сценарии

- [ ] 3.1 Ввести UseCases Watcher для бизнес-endpoints, перенести проверку типа перед чтением/обновлением из `AbstractWatcherTypeController`, определение пользователя закрытия инцидента — из контроллера в Domain. Проверить HTTP route guards, статусы ошибок и `CloseIncidentActionTest` после адаптации.
- [ ] 3.2 Убрать вызов Trace из `CheckWatchersTask`, получая контекст через собственный Domain Watcher. Проверить `CheckWatchersTaskTest`: один запрос размера буфера за проход, независимость проверок от брокера и сохранение поведения при недоступном MongoDB.
- [ ] 3.3 Создать локальные данные и UseCase Notification для события Watcher; перенести чтение watcher/последнего события из listener, сохранив snapshot инцидента. Проверить тесты listener, `EnqueueNotificationsActionTest` и `IncidentMessageFactoryTest`, включая отсутствие watcher, отсутствие события и сбой постановки.
- [ ] 3.4 Ввести UseCases каналов и отправки Notification, перенести проверку типа из контроллеров и получение данных из job в Domain. Проверить HTTP ответы, отправку тестового сообщения и `SendNotificationJobTest`: permanent failure, retry-after, backoff и уже отправленное уведомление.
- [ ] 3.5 Ввести UseCases Auth/User/Service; убрать вызовы User из Auth controllers/middleware через собственные Actions. Проверить HTTP тесты User/Service, login/logout/me и `AuthMiddlewareTerminateTest`, сохранив lifetime токена и обновление активности.
- [ ] 3.6 Ввести UseCases Cleaner/Setting/Dashboard; чтение Setting из Cleaner command/job перенести в Domain. Проверить `ClearTracesActionTest`, `SettingAdminHttpTest` и тесты Dashboard: сохранение очистки, ограничений settings и отсутствующего кэша статистики.
- [ ] 3.7 Ввести UseCases HTTP-операций Trace/Logs; выделить атомарные Actions из затронутых составных сценариев без изменения SQL и пакетной загрузки. Проверить тесты Trace/Logs, включая фильтры, дерево, индексацию и файловую очистку; Model/Builder не возвращаются из публичных методов репозиториев.
- [ ] 3.8 Ввести UseCases управления MCP и Tools; оставить JSON-RPC разбор/маршрутизацию в протокольной Infrastructure. Проверить MCP admin/endpoint tests и ответ ToolLinks; убедиться, что бизнес-endpoints вызывают один местный UseCase через DI.
- [ ] 3.9 Завершить очистку межмодульных типов в затронутых модулях, обновить матрицу связей, правила Actions/UseCases и репозиториев в `.ai/README.md`. Проверить отсутствие ActionResolver, proxy, чужих DTO в Entities/Parameters и прямого доступа UseCases к репозиториям/другим UseCases.

## 4. Dispatcher и маршруты доставки

- [ ] 4.1 Добавить `Common/Domain/Services/DispatcherInterface.php` и реализацию в `Common/Infrastructure/Services`, связывание в register Common provider. Добавить тесты event/report: исходный payload/exception, одна доставка, распространение исключения listener, сохранение фильтрации ожидаемых ошибок.
- [ ] 4.2 Реализовать идемпотентную регистрацию jobs и отказ для незарегистрированного класса через прямую запись в журнал. Проверить отсутствие отправки, повторной регистрации, рекурсивного report и сериализации payload/секретов в диагностике.
- [ ] 4.3 Реализовать проверку маршрутов по `queue.*` и `sconcur.queue.rabbitmq.*`, включая приоритет job, default queue и sync. Проверить разрешённые очереди при выключенных consumers, неизвестные connection/queue, sync без RabbitMQ и проброс ошибки брокера без retry dispatcher.
- [ ] 4.4 Добавить проверки сохранения delay/after-commit/очереди и изоляции параллельных dispatch. Проверить два одновременных вызова с разными payload/маршрутами и отсутствие состояния запроса в singleton.
- [ ] 4.5 Перевести события Trace/Watcher/Notification, три dispatch-listeners и репортинг Dashboard/MCP на интерфейс. Адаптировать тесты дерева, уведомлений и MCP; сохранить запись до события, немедленный broadcast и защиту успешного дерева при сбое трансляции.
- [ ] 4.6 Описать методы dispatcher, источник разрешённых маршрутов, sync и поведение отказов в `.ai/README.md`; сверить описание с тестами и убедиться, что Horizon/PMS reporter не добавлены в зависимости.

## 5. Регистрация модулей и расписание

- [ ] 5.1 Расширить `BaseServiceProvider` для регистрации собственных jobs/listeners, перенести bindings в register, сохранив существующие lifetimes. Проверить загрузку приложения, разрешение dispatcher и отсутствие static-кэшей контекста запроса.
- [ ] 5.2 Зарегистрировать пять application jobs в Trace/Notification/Cleaner/Dashboard, перенести mappings listeners из глобального EventServiceProvider в модульные providers. Проверить полный набор jobs и одну обработку каждого события после boot, без глобального перехвата jobs сторонних пакетов.
- [ ] 5.3 Удалить регистрацию `ClearTracesCommand` из Logs и прямую сборку Notification-сервиса через Watcher registry. Проверить доступность команды Cleaner и отсутствие запрещённых межмодульных зависимостей providers.
- [ ] 5.4 Перевести два scheduled job запуска в `app/Console/Kernel.php` на callbacks общего dispatcher. Проверить частоту, идентичность jobs/очередей и единственную отправку; команды и SConcur task pool сохраняют прежнее расписание.
- [ ] 5.5 Обновить в `.ai/README.md` правила регистрации и отправки jobs вместо прежнего требования helper dispatch. Проверить тестом десериализацию payload всех пяти jobs до рефакторинга без переименования FQCN/полей.

## 6. Доменные ошибки и HTTP

- [ ] 6.1 Добавить общий контракт/реестр exception managers в `Common/Infrastructure/Exceptions` и подключение из `app/Exceptions/Handler.php`/providers. Проверить fallback для неизвестной ошибки и отсутствие дублирующего репортинга.
- [ ] 6.2 Перенести HTTP mapping ошибок Watcher/Notification/User/Service/Setting из контроллеров в менеджеры, при необходимости добавить контекстные доменные ошибки. Проверить прежние статусы, тела и сообщения ошибок для отсутствия объекта, несовпадения типа и неверного значения.
- [ ] 6.3 Перенести mapping Trace/Logs/Dashboard/Auth и управления MCP с сохранением 401/404/409/422/503; протокольные ошибки MCP не направлять в HTTP-manager. Проверить HTTP, JSON-RPC, tool `isError` и `query_failed`, а также ошибки streamed responses, если затронут их путь.
- [ ] 6.4 Описать правила доменных исключений и транспортного отображения в `.ai/README.md`; проверить, что Domain не зависит от HTTP responses и принудительный PMS HTTP 400 не введён.

## 7. Итоговая интеграция

- [ ] 7.1 Выполнить полный набор архитектурных проверок и убедиться в отсутствии нарушений/временных исключений; сверить правила с DTO, providers и всеми текущими модулями, а не только fixtures.
- [ ] 7.2 Запустить регрессии HTTP/MCP/WebSocket, уведомлений, очистки и watchers из `tests/Modules`; подтвердить неизменность контрактов и независимость watchers от брокера. Проверить landing на необходимость обновления: его тексты и переиспользуемые представления должны оставаться актуальными.
- [ ] 7.3 Выполнить `make oa-generate`, проверить diff схем: изменения внешних полей, required/nullability и статусов не допускаются. При изменении сгенерированного TypeScript или frontend source выполнить `make frontend-npm-build`; иначе зафиксировать отсутствие основания для сборки.
- [ ] 7.4 Выполнить `make check` после завершения изменений и генерации. Зафиксировать успешный результат, проверить `git diff --check`, актуальность `.ai/README.md` и отсутствие изменений схемы БД/Go receiver; остановить сервисы, запущенные только для проверки.
