# Proposal

## Why

SLogger уже разделён на модули, но Deptrac проверяет только общие слои: доступ к чужому репозиторию и связи между DTO разных модулей могут пройти проверку. Составные сценарии, регистрация обработчиков и отправка событий распределены между Domain, контроллерами, listeners и глобальными провайдерами, поэтому правила архитектуры трудно соблюдать при развитии системы.

## What Changes

- Применить архитектурные правила `zb.pms.back`: отдельные проверки слоёв, межмодульных зависимостей и структуры каталогов; подключить их к `make check`.
- Разделить атомарные Actions и составные UseCases. UseCases вызывают Actions обычным DI, не репозитории и не другие UseCases. Контроллер вызывает один UseCase на endpoint бизнес-операции; jobs, listeners, tasks и MCP tools обращаются к Domain своего модуля. Разбор и маршрутизация протокола остаются в Infrastructure.
- Устранить зависимости между DTO разных модулей: создавать собственные Entities/Parameters и явные преобразования в `Transports` принимающего модуля, вызываемые из Domain, включая вложенные объекты, коллекции и MCP bridges. Исключений для мостов нет.
- Устранить обращения к чужим Infrastructure и Repositories, сохранить явно разрешённые направления вызовов между Domain модулей.
- Перенести `DispatcherInterface` с методами `event`, `dispatch`, `report`, `registerJobs`; адаптировать реализацию к Laravel/SConcur и существующим очередям. Зарегистрировать jobs и listeners в провайдерах модулей.
- Перенести устройство `ModulesConfig` и BaseServiceProvider из PMS: общий список модулей, `onRegister`/`onBoot`, собственные регистраторы команд/jobs/listeners/exception manager и `setSchedule` каждого модуля.
- Перенести ExceptionManagers, BaseExceptionManager и JsonHttpExceptionResponseObject из PMS: раздельные reportability и JSON rendering, unwrap streamed exceptions. Сохранить HTTP-контракты SLogger и отдельную обработку JSON-RPC/MCP tools.
- Проверить весь собственный PHP-код на длину строк до 120 (ориентир 100) и вертикальное оформление; закрыть пробелы автоматической проверки и повторно сверить весь перенос с первоисточником.
- Сохранить примитивы хранения и общий построитель фильтров; разделять чтение и запись только там, где этого требует выделение сценариев. Не переводить ClickHouse и MongoDB на Eloquent-шаблон.
- Обновить архитектурные инструкции проекта после реализации, включая правила событий, DTO, UseCases и проверки границ.

ActionResolver, proxy, автоматические retry действий, Horizon, запрет физического удаления и перенос watchers в очередь не входят в изменение. Обычный DI, существующий SConcur task pool и физическая очистка трейсов сохраняются.

- Перенести применимые к SLogger общие правила и навыки PMS и применять к создаваемым/затронутым файлам; не дублировать команды OpenSpec. Преобразования DTO размещать в модульном `Transports`.

## Capabilities

### New Capabilities

- `module-architecture`: проверяемые границы модулей и слоёв, собственные контракты данных, размещение сценариев и регистрация инфраструктуры.
- `application-dispatch`: единая отправка доменных событий и зарегистрированных jobs, проверка маршрута очереди и репортинг исключений через DI-контракт.

### Modified Capabilities

Нет. Требования `mcp-tools`, `mcp-trace-queries`, `mcp-protocol`, `mcp-connections`, `mcp-prompts`, `trace-storage` и `landing-page` сохраняются. Новые спецификации фиксируют архитектурный контроль и диспетчеризацию; HTTP/MCP, хранение и пользовательские сценарии не переопределяются.

## Impact

- Модули: `Common`, `Auth`, `User`, `Service`, `Setting`, `Cleaner`, `Dashboard`, `Logs`, `Trace`, `Watcher`, `Notification`, `Mcp`, `Tools`. Слои: Domain, Entities, Parameters, Enums, Repositories, Infrastructure; покрытие Models и точек сборки приложения уточняется в Deptrac.
- Проверки: `code-analyse/deptrac-layers.yaml`, новый межмодульный конфиг, проверка структуры/контрактов, `makefile`, соответствующие тесты архитектуры.
- Регистрация: `app/Modules/Common/Infrastructure/Providers/BaseServiceProvider.php`, модульные провайдеры, `app/Modules/ModulesConfig.php`, `app/Providers/EventServiceProvider.php`, `app/Console/Kernel.php`.
- Основные места переноса: `Mcp/Entities/Bridges`, `Mcp/Domain/Actions/Bridges`, `Mcp/Infrastructure/Tools`, `Notification/Infrastructure/Listeners`, `Watcher/Infrastructure/Http/Controllers`, `Watcher/Infrastructure/Tasks`, Actions событий Trace и Watcher.
- Конфигурация очередей: существующие `config/queue.php`, `config/sconcur.php`, `config/module-trace.php`, `config/cleaner.php`; имена очередей и параметры jobs сохраняются.
- Тесты: `tests/Modules/**`, архитектурные отрицательные примеры, регрессии HTTP/MCP и очередей. После реализации — `make check` и `make oa-generate`; frontend build только при изменении frontend/generated TypeScript.
- Внешние API, схема БД, формат очередных сообщений и Go receiver не меняются. Переименование jobs, уже находящихся в очереди, не допускается.

## Уточнение переноса code-analyse (2026-11-10)

Перенесённые пользователем исходные каталоги и алгоритмы PMS являются текущим
источником правил. Ранее добавленные локальные расширения удалены; адаптируются
только подключения, пути/autoload, отсутствующие PMS names и код под исходные проверки.
Действующая автоматическая проверка описана в design, раздел 1 и tasks, этап 10.
