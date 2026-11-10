# Proposal

## Why

SLogger не имеет статического тестового окружения и отдельных серверов хранения: интеграционные тесты могут использовать настройки рабочей среды. Изолированное тестовое окружение позволит проверять реальные репозитории и сценарии на изолированных базах в памяти с воспроизводимыми фабриками.

## What Changes

- Добавить MySQL, MongoDB, ClickHouse и Redis на tmpfs без host-портов и рабочих volumes, статический отслеживаемый .env.testing и команды test-up/test-prepare/test-art/test-down.
- Направить PHPUnit и тестовые Artisan-команды в это окружение; проверять изоляцию до обращения к данным, исключить рабочий config cache.
- Настроить базовый TestCase: appMake, запрет случайного HTTP, fake событий с явным opt-out для интеграции listeners, без RefreshDatabase/транзакционного отката каждого кейса.
- Добавить readonly фабрики SLogger через реальные репозитории и зависимые фабрики; интеграционные сценарии используют свежие сущности и проверяют состояние хранилищ без mocks Actions/Repositories.

## Capabilities

### New Capabilities

- `isolated-testing`: безопасное воспроизводимое выполнение тестов с отдельными временными хранилищами и фабриками сущностей.

### Modified Capabilities

Нет.

## Impact

Docker Compose, .env.testing, PHPUnit/bootstrap, makefile, tests/Factories и модульные тесты Auth/User/Service/Mcp/Watcher/Notification/Trace. Схема приложения и production Domain/Repositories не меняются; используются их существующие публичные API. Рабочие Redis и RabbitMQ не требуются: cache/queue по умолчанию заменяются array/sync, отдельный test-redis обслуживает конкурентные mutex-тесты.
