# Перенос инструкций PMS

Источник — `/home/spp/dev/projects/zb.pms.back`, commit
`0633fc0a1bf6e298429822d55afe7832ee357b86`.
Перенос выборочный: только правила и инструменты, применимые к SLogger.
Правила оформления скопированы дословно в [code-style.md](code-style.md);
остальные инструкции адаптированы к SLogger.

| Исходник PMS | Применение в SLogger |
| --- | --- |
| `.ai/README.md` | `.ai/code-style.md`: дословное оформление; `.ai/rules.md` + `.ai/README.md`: типы, DI, архитектура, миграции, исключения, git |
| `.ai/skills/tests.md` | `.ai/skills/tests.md`, местные TestCase, factories и команды |
| `.ai/skills/repositories.md` | `.ai/skills/repositories.md`, с сохранением SConcur/ClickHouse операций |
| `.ai/skills/admin.md` | `.ai/skills/admin.md`, Vue/Element Plus в `frontend/src` |
| `.ai/skills/documentation.md` | `.ai/skills/documentation.md`, OpenSpec и существующие README |
| `pms-review` / Claude review | Существующий `slogger-review` и `.claude/commands/slogger/review.md`; второй review не создаётся |
| `pms-env-actualize` | `slogger-env-actualize` для Codex и Claude |
| `opsx-*` wrappers / OpenSpec | Уже установленные официальные `openspec-*`; дубли не копируются |
| `NoMultilineArrayKeyRule` | `code-analyse/phpstan/NoMultilineArrayKeyRule.php`, подключён в PHPStan |
| Четыре custom CS fixers и их traits | `code-analyse/cs-fixer/fixers`, единый конфиг `code-analyse/cs-fixer/.php-cs-fixer.dist.php` для штатных check/fix |
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

## Подключение исходной структуры code-analyse

После пользовательского переноса исходные fixers, NoMultilineArrayKeyRule и
Deptrac matrices используются из cs-fixer/phpstan/deptrac без изменения алгоритмов.
В makefile исправлены пути, Composer регистрирует CodeAnalyse\PhpStan, standalone
module-structure CLI не зависит от удалённого ModuleStructure-класса. Старые локальные
InputParametersRule/DomainDependencyRule и line-length check не восстановлены.
В PHPStan удалены только unmatched ignore patterns отсутствующих PMS интеграций;
проверки и область app/tests/seeders сохранены. Уточнены реальные типы тестов и
инициализация TraceDataToObjectBuilder. Часть предыдущих утверждений об автоматической
проверке бизнес-матрицы и входных Parameters больше не соответствует этому набору:
эти требования остаются правилами ревью, а не дополнительной переделкой исходников PMS.

Кеши анализаторов хранятся рядом с их настройками: CS Fixer — в cs-fixer,
PHPStan — в phpstan/.cache, две конфигурации Deptrac — в отдельных
.deptrac-layers.cache/.deptrac-modules.cache внутри deptrac. Локальные .gitignore
этих каталогов исключают кеши из git.

Для YAML-конфига Deptrac используется штатный раздел deptrac: cache_file внутри
старого parameters не управляет кешем в установленной версии. Состав layers/ruleset
сохранён; оба прогона подтверждают прежние количества проверенных зависимостей.

### Базовые исключения модулей

Перенесена иерархия PMS `Abstract<Module>Exception extends Exception` в 11 модулей
с доменными ошибками, включая Common. Все конкретные Domain Exceptions наследуют
свой базовый тип; менеджеры распознают модуль через него, а dontReport сохраняет
конкретные классы. CommonExceptionManager зарегистрирован собственным provider.
Новые наследники получают общий безопасный ответ и остаются reportable.

Сохранены конкретные HTTP-статусы и сообщения SLogger, Dashboard error/503 и MCP
protocol/tool handling. В отличие от PMS, MutexLockTimeoutException сохраняет
SLogger HTTP 500 (в PMS это 400); внутренние ошибки Logs/MCP также остаются 500.
Общий ответ Server Error не раскрывает путь/настройки/ключ блокировки, оригинал
исключения остаётся доступен report. Модули без Domain Exceptions не получают
неиспользуемые базовые классы. Locale/translation-specific PMS dependencies не нужны
для текущих английских сообщений SLogger.

Уточнение Auth: текст ответа задаёт само исключение, менеджер возвращает getMessage;
AuthenticationFailedException содержит Authentication failed. Сообщения наследников
Auth должны быть пригодны для пользователя. Ошибка callback after-lock в MutexManager
оборачивается в RuntimeException с исходным previous после освобождения блокировки.

### Уточнение оформления по пользовательскому коммиту f818eb56

Оформление сверено с PMS и пользовательскими примерами: короткие сигнатуры,
config/helper calls и простые вложенные вызовы компактны; сложные аргументы,
тернарные ветки и регистрационные списки раскрываются по смыслу. Прежний запрет
любой однострочной вложенности удалён из правил; Resource с результатом UseCase
сохраняет отдельную строку. Исходные алгоритмы/config CS Fixer не менялись.
В DTO JSON-ошибки используется пользовательский bool isError вместо messageField;
поля и статусы HTTP-ответов сохранены, boolean-ожидания тестов актуализированы.

### Дословные правила оформления PMS

Разделы оформления из `.ai/README.md` и ассерты из `.ai/skills/tests.md` PMS
скопированы в `.ai/code-style.md` без пересказа. Этот текст заменяет прежнее
описание оформления в `.ai/rules.md`; прямые уточнения пользователя сохранены.
Именованные аргументы вертикальны с trailing comma (ассерты — без неё),
порядок соответствует сигнатуре. Обычные одноаргументные вызовы обязательного
параметра не получают ненужных имён. Большие iterables вынесены перед foreach
без материализации генераторов. Исходники code-analyse остаются без изменений.

Конструкторы Object/Parameters используют именованные аргументы и при единственном
обязательном поле, как предписывает отдельное правило DTO в PMS.
