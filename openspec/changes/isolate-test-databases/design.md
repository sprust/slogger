# Design

## Context

Тестовое окружение использует test-mysql/test-mongo/test-clickhouse на tmpfs, статический .env.testing, test-prepare с миграциями, BaseModuleTestCase и readonly repository factories. SLogger использует SConcur SQL/Mongo/HTTP; migrate:fresh обслуживает все три хранилища.

## Goals / Non-Goals

**Goals:** реальные отдельные движки, запуск без рабочих БД, защитное разделение окружений, удобные фабрики и интеграционные сценарии по правилам тестов.

**Non-Goals:** SQLite вместо MySQL, эмуляция Mongo/ClickHouse, изменение production схемы и массовое переписывание незатронутых unit-тестов.

## Decisions

- Сервисы profile testing без портов и persistent volumes, с фиксированными service hostnames. ClickHouse сохраняет production version и профиль настроек, данные и логи временные.
- .env.testing содержит только публичные тестовые значения; не создаётся копированием .env. Bootstrap тестов загружает его до Laravel, игнорирует рабочий config cache. Guard проверяет environment, соединения, URL и адреса до boot providers.
- Отдельный Redis без persistence обслуживает существующие тесты конкурентных mutex. Рабочие Redis/RabbitMQ не нужны.
- tests/console.php использует тот же bootstrap для test-art и подготовки; только после проверки запускает Artisan. test-prepare вызывает существующий migrate:fresh и не требует production DB/services.
- TestCase сохраняет очистку SConcur context, добавляет Http::preventStrayRequests; CreatesApplication устанавливает Event::fake до boot providers, пока singleton dispatcher ещё не создан. opt-out — protected flag для проверок listeners; BaseModuleTestCase предоставляет appMake без бизнес-контекста.
- Фабрики User, UserToken, Service, Mcp, Channel, Watcher, Incident, Notification и Trace возвращают идентификаторы. SQL/Mongo-фабрики используют repositories, Trace пишет через существующий ClickhouseClient, поскольку production insert принадлежит Go receiver. Никаких новых production mutation-методов ради тестов.
- Каждый сценарий создаёт уникальные parent-сущности; rollback/truncate между кейсами не применяется. Чистые DTO fixtures остаются в памяти. Проверяем SQL-аутентификацию, Mongo-инцидент/уведомление и выборку трейсов через реальные публичные API.

## Risks / Trade-offs

- Рабочие env/cached config → общий bootstrap и guard до boot providers, отрицательные тесты.
- tmpfs ограничен памятью → небольшие тестовые лимиты, команды остановки, fresh после перезапуска.
- Event fake скрывает listener regression → явный opt-out в существующем integration suite.
- Данные накапливаются → уникальные идентификаторы и повторный запуск без reset, test-prepare для очистки всей изолированной среды.

## Migration Plan

Запустить test-prepare, затем make check. После остановки tmpfs-сервисов требуется повторная подготовка. Рабочая .env и данные остаются прежними. Откат тестовой инфраструктуры не требует отката БД приложения.
