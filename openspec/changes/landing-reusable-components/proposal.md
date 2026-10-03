# Proposal

## Why

Публичная страница-презентация (change `landing-page`) показывает работу SLogger на демо-данных теми же компонентами, что и админка. Сейчас компоненты дерева трейса, графиков и событий инцидента сами читают глобальные Pinia store, ходят в API и переходят по маршрутам админки, поэтому вне админки их не отрисовать. Этот change выносит отображение в компоненты, которые получают данные только через props, и не меняет поведение админки.

## What Changes

- Новый компонент `TraceTreeRowView`: строка дерева трейса без store. Данные и флаги выделения приходят в props, действия уходят событиями. `TraceAggregatorTraceTreeRow` становится обёрткой: берёт данные из store и передаёт их в `TraceTreeRowView`.
- `TraceAggregatorTraceTreeVirtual` рисует строку через слот `#row` и больше не импортирует `TraceAggregatorTraceTreeRow`. `TraceAggregatorTraceTree` передаёт в слот обёртку со store.
- Новый компонент `TraceMetricsChart`: столбчатый график метрик трейсов сервиса (logged / buffered / stored по 15-минутным слотам) по переданным строкам и выбранным типам. Расчёт значений выносится в модуль без Vue. `DashboardMetricsServiceChart` оставляет у себя загрузку, шапку с итогами, выбор типов и переход в агрегатор.
- Новый компонент `TraceTimelineChart`: один график агрегатора по переданным `data` и `options`, клик по столбцу уходит событием с индексом. `TraceAggregatorGraph` оставляет у себя опрос, live-режим и перенос периода в фильтр.
- Новый компонент `IncidentEventsTable`: таблица событий инцидента с раскрытием групп трейсов. На вход — события, колонки, имена сервисов и состояние загрузки. Наружу — события «показать ещё», «раскрыть строку», «открыть в агрегаторе». Набор колонок по типу смотрителя переезжает в отдельный модуль. `IncidentEvents` оставляет у себя store, загрузку и переход в агрегатор.
- Чистые функции дат переезжают из `utils/helpers.ts` в `utils/utcDateTime.ts` (helpers их реэкспортирует), константа слота метрик — из store дашборда в модуль расчёта значений. Так компоненты отображения не тянут store через вспомогательные модули.
- Профилирование не затрагивается: его будут удалять отдельно.
- Поведение админки не меняется: те же данные, разметка, подписи и переходы.

## Capabilities

### New Capabilities

Нет.

### Modified Capabilities

Нет. Это рефакторинг фронтенда без изменения поведения, поэтому в `.openspec.yaml` стоит `skip_specs: true`. Требования к лендингу описывает change `landing-page`.

## Impact

- Только фронтенд, `frontend/src/components/pages`:
  - `trace-aggregator/components/tree`: `TraceAggregatorTraceTreeRow.vue`, `TraceAggregatorTraceTreeVirtual.vue`, `TraceAggregatorTraceTree.vue`, новый `TraceTreeRowView.vue`;
  - `trace-aggregator/components/graph`: `TraceAggregatorGraph.vue`, новый `TraceTimelineChart.vue`;
  - `dashboard`: `DashboardMetricsServiceChart.vue`, `store/dashboardMetricsStore.ts`, новые `TraceMetricsChart.vue` и модуль расчёта значений;
  - `watchers/components/incidents`: `IncidentEvents.vue`, новые `IncidentEventsTable.vue` и модуль колонок событий.
- `frontend/src/utils`: `helpers.ts`, новый `utcDateTime.ts`.
- `app/Modules` и слои Deptrac не затрагиваются, бэкенд, API и OpenAPI не меняются.
- Новых зависимостей нет.
