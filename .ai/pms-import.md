# Перенос инструкций PMS

Источник — `/home/spp/dev/projects/zb.pms.back`, commit
`0633fc0a1bf6e298429822d55afe7832ee357b86`.
Перенос выборочный: только правила и инструменты, применимые к SLogger.
Копий исходных PMS-инструкций в репозитории нет.

| Исходник PMS | Применение в SLogger |
| --- | --- |
| `.ai/README.md` | `.ai/rules.md` + существующий `.ai/README.md`: стиль, типы, DI, архитектура, миграции, исключения, git |
| `.ai/skills/tests.md` | `.ai/skills/tests.md`, местные TestCase, factories и команды |
| `.ai/skills/repositories.md` | `.ai/skills/repositories.md`, с сохранением SConcur/ClickHouse операций |
| `.ai/skills/admin.md` | `.ai/skills/admin.md`, Vue/Element Plus в `frontend/src` |
| `.ai/skills/documentation.md` | `.ai/skills/documentation.md`, OpenSpec и существующие README |
| `pms-review` / Claude review | Существующий `slogger-review` и `.claude/commands/slogger/review.md`; второй review не создаётся |
| `pms-env-actualize` | `slogger-env-actualize` для Codex и Claude |
| `opsx-*` wrappers / OpenSpec | Уже установленные официальные `openspec-*`; дубли не копируются |
| `NoMultilineArrayKeyRule` | `code-analyse/PHPStan/NoMultilineArrayKeyRule.php`, подключён в PHPStan |
| Четыре custom CS fixers и их traits | `code-analyse/Fixers`, единый конфиг `code-analyse/php-cs-fixer.dist.php` для штатных check/fix |
| Exception managers | Исходный Manager API, BaseExceptionManager, DTO и ReportableException; сохранены статусы/JSON Dashboard SLogger, без PMS LocaleRules и ErrorReport |
| ModulesConfig и BaseServiceProvider | Единый список, hooks, модульные регистраторы и расписание; `getContracts` сохраняет SLogger singleton lifetime, без PMS AppContext/static caches |
| Тестовые tmpfs DB, .env.testing и factories | Изолированные MySQL/MongoDB/ClickHouse и Redis, guarded bootstrap, BaseModuleTestCase и readonly factories сущностей SLogger; без PMS seeders/context |
| OpenAPI generator | Локальный пакет и SourceInterface; только admin API SLogger, public/private aliases, без Go traces-api и PMS collection-классов |
| Deptrac и проверка структуры | Адаптированы в локальных configs; включают Enums, Common, providers, UseCases и Transports |

Не перенесены навыки удаления отелей, клиентских разрешений и подготовки
промпта для отдельного фронтенда PMS. SLogger содержит собственный frontend;
правила его изменения находятся в `skills/admin.md`. Отдельный навык доступа
не нужен: действующие middleware и ограничения описаны в README.

Согласованные отличия:

- обычный DI, без ActionResolver/proxy;
- физическое удаление и история удалённых объектов сохраняются;
- Common, события, исключения и Transports анализируются; чужие DTO не исключены
  из правил и не помещаются в Common ради обхода;
- преобразователи `*Transport` в модульном `Transports` принимают Entity/Object;
  другие публичные Domain-методы принимают примитивы, enum и местные Parameters;
- вместо Horizon, TelegramReporter и PMS ErrorReport используется Dispatcher
  SConcur/Laravel с существующим ExceptionHandler и неизменными HTTP-статусами;
- модульные команды/пути и тестовая инфраструктура берутся из SLogger; нет
  pms-bmr, отелей, CustomerContext, PMS permissions и соответствующих миграций;
- импорт правил не означает массовый переход существующих репозиториев на
  BuilderMaker/RawRepository или переписывание незатронутого кода;
- исходные `.claude/settings.json` с permissions не переносятся: права исполнения
  задаёт окружение, обязательные проверки перечислены в `.ai/README.md`.

PHP CS Fixer использует единый конфиг PMS с путями SLogger. Все четыре custom
fixers, исключения Models/Repositories, правила переноса аргументов, выравнивания
и кеш с версией исходников подключены в штатные `make code-analise-cs-fixer-check`
и `make code-analise-cs-fixer-fix`. Обёртка diff сохраняет код ошибки команды.
Для первоначального подключения выполняется форматирование проверяемых файлов;
бизнес-поведение при этом не меняется. Каталоги интеграций PMS не переносятся.
Текстовые правила для fluent-цепочек и логических блоков требуют отдельной проверки.

## Повторная сверка переноса

Проверены исходники PMS commit `0633fc0a` и текущая версия: providers и их
регистраторы, ModulesConfig, dispatcher, четыре custom fixers, OA pipeline,
тестовые tmpfs-сервисы и bootstrap. Подтверждены описанные выше адаптации.
Исправлены самодельный map API исключений, потеря семантики явного report и
принудительная data-обёртка OA для ресурсов с withoutWrapping.

Formatter охватывает собственный PHP в app, packages, tests, database/seeders,
config, routes, bootstrap и code-analyse. database/migrations сохраняет прежнее
оформление и исключён из массового форматирования и проверки длины строк. Добавлена проверка строк до 120 символов;
namespace/use и содержимое heredoc fixtures исключаются, значения строк сохраняются.
`inline_constructor_arguments=false` сохраняет перенос длинного конструктора
анонимного класса; enum cases стоят перед методами. Короткие простые вызовы,
условия и небольшие массивы допускаются в строку по уточнениям пользователя.
Заголовок foreach всегда однострочный: большие выражения обхода выносятся
в переменную. Formatter не разворачивает его вложенные вызовы; regex helpers
оставляются компактными. Короткий pcntl_signal callback-префикс сохраняется.
Вложенный вызов в аргументе конструктора/метода раскрывается на отдельной строке,
в частности new SconcurStatResource с результатом UseCase.

Оставшиеся результаты аудита, которые нельзя выдавать за завершённый перенос:

- 52 публичных входа Entity/Repository DTO в Domain существовали ещё в master;
  новая проверка входных Parameters не контролирует это правило целиком.
- Созданные CheckWatcherParameters, RegisterTriggerParameters,
  SendNotificationParameters и IncidentEventParameters содержат Entities,
  а PMS требует примитивы, enum и вложенные Parameters. Обёртка сама по себе
  не устраняет нарушение входного контракта.
- В SLogger PHPStan пока не охватывает весь tests/seeders и не включает общие
  checkUninitializedProperties/treatPhpDocTypesAsCertain:false из PMS.
  Отдельный audit-конфиг выявил 208 ошибок тестов; перенос путей без исправлений
  не будет зелёной проверкой. Ошибочное checkedExceptionClasses из PMS копировать
  нельзя: regex там воспринимается как имя класса.
- Изменённые EnqueueNotificationsActionTest и SendNotificationActionTest сохраняют
  mocks Actions/Repositories; их сценарии ещё нужно перевести на factories/DB.
  Новые интеграционные проверки не заменяют эти сценарии.

Эти находки отделены от подтверждённой эквивалентности форматирования и исправленного
механизма исключений; они требуют отдельного функционального изменения Domain и тестов.
