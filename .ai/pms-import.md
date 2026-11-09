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
| Четыре custom CS fixers и их traits | `code-analyse/Fixers`, конфиг `code-analyse/php-cs-fixer-pms.php` для затронутых PHP-файлов |
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

Custom formatter применяется к создаваемым и изменяемым PHP-файлам командой:
`php vendor/bin/php-cs-fixer fix --config=code-analyse/php-cs-fixer-pms.php --using-cache=no <paths>`.
Для проверки добавить `--dry-run --diff`. Старые файлы не форматируются только
ради установки новых правил. Основной `make check` по-прежнему проверяет всю архитектуру.
