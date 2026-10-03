# Tasks

В фронтенде нет тестов: типы и шаблоны проверяет `vue-tsc` в `make frontend-npm-build`, поведение — ручная проверка админки. Если для проверки поднимались сервисы, после неё они останавливаются.

## 1. Строка дерева трейса

- [x] 1.1 Создать `trace-aggregator/components/tree/TraceTreeRowView.vue`: props и события из design.md, раздел 3, разметка и стили строки перенесены из `TraceAggregatorTraceTreeRow.vue` без изменений, store и роутер не импортируются (проверка: `grep -n "Store\|router" TraceTreeRowView.vue` находит только `import type`)
- [x] 1.2 Переписать `TraceAggregatorTraceTreeRow.vue` в обёртку: флаги и `indicatorWidthPercent` считаются прежними выражениями из store, события вызывают прежние методы store (проверка: `vue-tsc` без ошибок)
- [x] 1.3 В `TraceAggregatorTraceTreeVirtual.vue` заменить строку на `<slot name="row" :row="row"/>` и убрать импорт строки. В `TraceAggregatorTraceTree.vue` передать в слот `TraceAggregatorTraceTreeRow` (проверка: в агрегаторе дерево трейса раскрывается, догружает детей по «more», клик выделяет строку и открывает данные, «tree», «json», «indicate» работают, подсветка выбранных сервиса, типа, тегов и статуса на месте)

## 2. График метрик сервиса

- [x] 2.1 Вынести расчёт значений по слотам, итогов и списка типов в `dashboard/traceMetricsValues.ts` как чистые функции, возвращающие объекты, туда же — `metricSlotMinutes` с реэкспортом из store. Функции дат вынести в `utils/utcDateTime.ts` с реэкспортом из `helpers.ts` (проверка: `vue-tsc` без ошибок)
- [x] 2.2 Создать `dashboard/TraceMetricsChart.vue`: props `slots`, `rows`, `selectedTypes`, `clickHint`, событие `slot-click(index)`, высота от родителя, курсор, подвал тултипа и клик только при непустом `clickHint` (проверка: store и роутер не импортируются во время выполнения)
- [x] 2.3 Переписать `DashboardMetricsServiceChart.vue` в обёртку над `TraceMetricsChart` и `traceMetricsValues.ts` (проверка: на дашборде график сервиса грузится, Refresh обновляет, выбор типов меняет столбцы и итоги в шапке, клик по столбцу открывает агрегатор с сервисом, типами и периодом слота)

## 3. График агрегатора

- [x] 3.1 Создать `trace-aggregator/components/graph/TraceTimelineChart.vue`: props `data`, `options`, `height`, событие `bar-click(index)` по своему графику (проверка: store не импортируются)
- [x] 3.2 Переписать `TraceAggregatorGraph.vue`: шаблон рисует `TraceTimelineChart` на каждый график, `bar-click` переводится в прежнюю логику `onGraphClick`, опрос, таймер и `beforeUnmount` не меняются (проверка: график агрегатора рисуется с одним и несколькими полями, live-режим обновляется и останавливается при уходе со страницы, клик по столбцу ставит период и закрывает график)

## 4. События инцидента

- [x] 4.1 Вынести `PayloadColumn`, `setting`, `measured` и `columnsByType` в `watchers/components/incidents/incidentEventColumns.ts` (проверка: `vue-tsc` без ошибок)
- [x] 4.2 Создать `watchers/components/incidents/IncidentEventsTable.vue`: props и события из design.md, раздел 7, заглушки и раскрытие групп перенесены без изменений, при `canOpenInAggregator = false` кнопка скрыта через `visibility: hidden` (проверка: store и роутер не импортируются во время выполнения)
- [x] 4.3 Переписать `IncidentEvents.vue` в обёртку над `IncidentEventsTable` (проверка: на странице смотрителей раскрытие инцидента загружает события, колонки соответствуют типу смотрителя, раскрытые строки запоминаются, «Show more» догружает, кнопка-фильтр открывает агрегатор с заполненным фильтром, у удалённого смотрителя показывается заглушка)

## 5. Проверка

- [x] 5.1 `make frontend-npm-build` проходит без ошибок `vue-tsc` и `vite build`
- [x] 5.2 Новые компоненты отображения не тянут store во время выполнения: `grep -nE "^import [^t].*(Store|store|router|apiContainer)" TraceTreeRowView.vue TraceMetricsChart.vue TraceTimelineChart.vue IncidentEventsTable.vue traceMetricsValues.ts incidentEventColumns.ts utcDateTime.ts` ничего не находит, кроме импорта типов
- [x] 5.3 Пройти ручной чек-лист из групп 1–4 в запущенной админке. Остановить поднятые для проверки сервисы
