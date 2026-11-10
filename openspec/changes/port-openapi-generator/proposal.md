# Proposal

## Why

Локальный генератор SLogger требует обновления: маршруты задаются общей картой, отсутствуют расширяемые источники, явные статусы ответов и поддержка части типизированных контрактов. Нужен перенос актуального пакета с адаптацией к существующим API и frontend.

## What Changes

- Обновить пакет OpenAPI целиком, удалить заменённую структуру и подключить источники admin-api через SourceInterface.
- Адаптировать конфигурацию, диски, команды генерации, импорты и тесты к SLogger; сохранить имена файлов схем и действующие HTTP-контракты.
- Проверить nullable enum, union ресурсов, корневые массивы запросов и статусы; регенерировать TypeScript штатной командой.

## Capabilities

### New Capabilities

- `openapi-generation`: генерация схем из типизированных Laravel запросов и ресурсов через зарегистрированные источники.

### Modified Capabilities

Нет. Внешнее поведение API не меняется.

## Impact

`packages/ifksco/openapi-generator`, `app/Services/OpenApiGenerator`, конфигурация, makefile, generated схемы/TypeScript и тесты. Слои модулей не меняются; используются существующие Infrastructure Requests/Resources всех модулей. Модели, Domain, хранилища и receiver не затрагиваются. Go traces-api исключён из Laravel-генерации.
