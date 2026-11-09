# Design

## Context

Источником служит `zb.pms.back/packages/ifksco/openapi-generator`. SLogger использует локальный path package, старые namespace Converters/Json/Router, конфигурацию routes и `oa:generate` без аргументов. Frontend читает `storage/api/json-schemes/admin-api-openapi-scheme.json`.

## Goals / Non-Goals

**Goals:** перенести актуальную архитектуру и возможности генератора, сохранить действующий контракт SLogger, проверить генерацию и клиент.

**Non-Goals:** перенос PMS business sources, контекстов приложения, дополнительных API или их middleware; изменение маршрутов, доменных сценариев и хранилищ.

## Decisions

- Полная замена локального пакета версией PMS без параллельных старых классов. Допустимые адаптации исправляют совместимость SLogger; фиксируются тестами.
- Источник в `app/Services/OpenApiGenerator/Sources/Illuminate` наследует BaseRouterParser и задаёт существующие префиксы, названия и адрес сервера SLogger.
- Конфиг использует sources, список disks и resources_parent_classes. `make oa-generate` вызывает генерацию public/private alias, затем прежний frontend generator.
- Типы дополнительных enum и расширения схем берутся из генератора, предметные converters PMS не копируются.
- Сравниваем схемы до/после: допускаются новая корректная структура enum/ref и метаданные, но не потеря полей, маршрутов, статусов или nullable. Существующие особенности SLogger сохраняются адаптацией генератора.
- Тесты получают парсеры из контейнера, используют настоящие типизированные fixtures Requests/Resources и локальный диск; mocks/Reflection для вызова private API не нужны.

## Risks / Trade-offs

- Отличия namespace и команд → обновить config/импорты/Composer metadata и проверить отсутствие старых ссылок.
- Изменение описания типов ломает клиент → выполнить генерацию, исследовать diff, собрать frontend.
- PMS parser теряет особенности SLogger → перенести необходимые исправления с регрессиями.

## Migration Plan

Заменить пакет и конфиг одновременно; регенерировать артефакты и выполнить проверки. Данные и рабочие БД не мигрируются. Откат — соответствующий код и сгенерированные файлы одним изменением.
