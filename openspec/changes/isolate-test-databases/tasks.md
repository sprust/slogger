# Tasks

## 1. Изолированное окружение

- [x] 1.1 Добавить четыре tmpfs-сервиса и статический .env.testing, bootstrap/guard и test-команды; проверить compose config, подготовку всех схем и отказ для рабочих адресов.
- [x] 1.2 Адаптировать TestCase/BaseModuleTestCase, HTTP/event defaults и существующую проверку listeners; проверить PHPUnit на статической конфигурации и обновить гайд тестов.

## 2. Фабрики и сценарии

- [x] 2.1 Добавить типизированные readonly фабрики через repositories/ClickhouseClient и публичные сценарии SQL/Mongo/ClickHouse без mocks; проверить фактические данные и повторный запуск без reset.
- [x] 2.2 Обновить README и инструкции, выполнить make check при остановленных рабочих БД, OpenSpec strict validation, git diff --check и ревью; остановить поднятые сервисы и записать результаты.

## Проверка

- `make test-prepare`: миграции MySQL, MongoDB и ClickHouse выполнены на tmpfs.
- `docker-compose --profile testing config --quiet`: успешно.
- `make check`: 932 теста, 3209 assertions; CSFixer, PHPStan, Deptrac и ModuleStructure без ошибок.
- Повторные интеграционные сценарии без reset: 10 тестов, 35 assertions; затем полный suite также прошёл на накопленных данных.
- Guard/bootstrap: 12 тестов, 23 assertions; рабочие переменные и опасные overrides не используются.
- Регрессия раннего Event fake и сохранения job registry вместе с закрытием инцидента: 3 теста, 12 assertions.
- OpenSpec strict validation, `git diff --check`, code-reviewer: PASSED.
- Проверены и обновлены README.md, README.ru.md, .ai/README.md, .ai/skills/tests.md и .ai/architecture-notes.md.
- Все запущенные для работы сервисы остановлены; рабочие БД были выключены во время финальных проверок.
