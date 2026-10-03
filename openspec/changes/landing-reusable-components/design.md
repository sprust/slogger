# Design

## Context

Мотивация — в `proposal.md`, раздел Why. Сейчас компоненты устроены так:

- `TraceAggregatorTraceTreeRow` читает `useTraceAggregatorTreeStore` (выбранный трейс, `parameters.trace_id`, индикаторы, выбранные сервисы, типы, теги, статусы, `servicesMap`) и `useTraceAggregatorServicesStore` (имена сервисов). Все действия строки — `findData`, `initTreeByRow`, `showBranchJson`, `fillTreeIndicatorsByRow`, `toggleCollapse`, `loadLazyChildren` — это вызовы store.
- `TraceAggregatorTraceTreeVirtual` получает `items` через props, но строку рисует жёстко: `<TraceAggregatorTraceTreeRow :row="row"/>`. Высота строки 30px, видимое окно считается от `clientHeight` контейнера.
- `DashboardMetricsServiceChart` при `created` вызывает `metricsStore.chart(service.id)`. Значения по слотам, итоги и список типов он считает сам из `chart.rows` и `chart.slots`. По клику на столбец заполняет фильтр агрегатора и делает `$router.push`.
- `TraceAggregatorGraph` рисует `graphStore.graphs` с общими `graphOptions`, ведёт live-опрос (`playGraph`, таймер, `mirrorGraphWindowIntoFilter`). Клик по столбцу берёт `$refs.tracesGraphRef[0].chart`, то есть всегда первый график.
- `IncidentEvents` берёт события, флаги загрузки и раскрытые строки из `useIncidentsStore`, тип смотрителя из `useWatchersStore`, имена сервисов из `useTraceAggregatorServicesStore`. Колонки по типу смотрителя (`columnsByType`) объявлены прямо в компоненте. Переход в агрегатор идёт через `applyExternalFilter` и `$router.push`.

## Goals / Non-Goals

**Goals:**
- В каждом из пяти мест отделить отображение от данных: компонент отображения получает всё через props и сообщает о действиях событиями.
- Компоненты отображения не импортируют store, API-клиент и роутер во время выполнения. Импорт типов (`import type`) допустим.
- Админка выглядит и работает как раньше.

**Non-Goals:**
- Лендинг, маршрут `/landing`, демо-данные — это change `landing-page`.
- Изменения store, API, бэкенда, кроме переноса константы слота из раздела 8.
- Профилирование: его будут удалять отдельно, лендинг его не показывает.
- Переход на `<script setup>` и другие стилистические переделки сверх нужного для разделения.

## Decisions

### 1. Обёртка и компонент отображения, а не подмена store

Компонент отображения (`*View`, `*Chart`, `*Table`) получает props и отдаёт события. Старый компонент остаётся на прежнем месте, сохраняет имя и становится обёрткой: берёт данные из store, передаёт их в props, а события переводит в вызовы store и роутера.

Отвергнутые варианты:
- Наполнять существующие store демо-данными на лендинге. Store глобальные, поэтому демо-данные попали бы в админку при переходе. К тому же часть компонентов сама ходит в API при монтировании.
- Переносить в props всю логику, включая загрузку. Тогда store и роутер протекли бы в лендинг, а это противоречит цели.

Родители (`TraceAggregatorTraceTree`, `DashboardMetrics`, `TraceAggregatorTraces`, `Incidents`) продолжают подключать обёртки с теми же props. Их правка нужна только для слота виртуального списка.

### 2. Где лежат новые компоненты

Рядом с обёртками, в тех же папках страниц:
- `trace-aggregator/components/tree/TraceTreeRowView.vue`;
- `trace-aggregator/components/graph/TraceTimelineChart.vue`;
- `dashboard/TraceMetricsChart.vue` и `dashboard/traceMetricsValues.ts`;
- `watchers/components/incidents/IncidentEventsTable.vue` и `watchers/components/incidents/incidentEventColumns.ts`.

Страницы админки уже импортируют друг друга (например, `dashboard` → `trace-aggregator`). Общая папка `components/views` оторвала бы компонент от его единственной обёртки. Если появится третий потребитель, вынести их будет несложно.

### 3. Строка дерева: `TraceTreeRowView`

Props:
- `row: TraceTreeNode`;
- `serviceName: string`;
- `selected: boolean` — строка выбрана (сейчас красная точка);
- `highlighted: boolean` — это трейс из `parameters.trace_id` (сейчас зелёная рамка);
- `indicatorWidthPercent: number` — ширина индикатора длительности, сейчас считается в `makeTraceIndicatorStyle`;
- `serviceSelected`, `typeSelected`, `statusSelected: boolean`;
- `selectedTags: Array<string>` — теги, которые подсвечиваются.

События: `select` (клик по строке), `toggle-collapse`, `find-tree`, `show-json`, `indicate`, `load-more`.

Обёртка `TraceAggregatorTraceTreeRow` считает флаги теми же выражениями, что сейчас (`indexOf` по массивам store), и вызывает те же методы store. Разметка, классы и стили строки переезжают в `TraceTreeRowView` без изменений. `getIndicatorBackground`, `hasChildren` и `loadMoreLabel` зависят только от `row` и тоже переезжают.

### 4. Виртуальный список: слот `#row`

`TraceAggregatorTraceTreeVirtual` рисует `<slot name="row" :row="row"/>` и не импортирует строку. `TraceAggregatorTraceTree` передаёт в слот `<TraceAggregatorTraceTreeRow :row="row"/>`. Строки по умолчанию в слоте нет: иначе виртуальный список по-прежнему тянул бы за собой store-обёртку, а вместе с ней store и API-клиент в бандл лендинга.

### 5. График метрик: `TraceMetricsChart` и `traceMetricsValues.ts`

Расчёт `values` по слотам, `totals` и список `types` переезжает в `traceMetricsValues.ts`: чистые функции над `slots`, `rows` и `selectedTypes`, без Vue. Результат — объекты (`MetricTotals`, `MetricType`, значения серий), а не массивы с ключами-смыслами.

`TraceMetricsChart` получает props `slots`, `rows`, `selectedTypes` и необязательный `clickHint: string`. Сам строит `chartData` и `chartOptions`, рисует `Bar` на всю высоту родителя и отдаёт событие `slot-click(index)`. Высоту задаёт родитель: в панели это прежний блок 260px с `v-loading` и заглушкой, на лендинге — свой блок. Курсор-указатель, подвал тултипа и событие клика включаются только при непустом `clickHint`, поэтому на лендинге график не обещает перехода.

Обёртка `DashboardMetricsServiceChart` оставляет у себя карточку, шапку (имя, итоги через `traceMetricsValues.ts`, выбор типов, Refresh), загрузку через store, заглушку «Not loaded» и `openInAggregator`. В `clickHint` передаёт `'Click to open in the aggregator'`.

### 6. График агрегатора: `TraceTimelineChart`

Props: `data`, `options`, `height: string`. Компонент рисует один `Bar` со своим `ref` и отдаёт `bar-click(index)`. `TraceAggregatorGraph` перебирает `graphStore.graphs`, рисует подпись и `TraceTimelineChart` для каждого графика, а `bar-click` переводит в прежнюю логику `onGraphClick`: `metrics[index]` → `logging_from`/`logging_to`, `showGraph = false`. Опрос, таймер, `beforeUnmount` и watch остаются в обёртке без изменений.

Побочный эффект: клик по второму и следующим графикам начнёт определять столбец по своему графику, а не по первому. Все графики строятся по одному массиву `metrics` с одинаковыми метками, поэтому индекс совпадает и результат клика не меняется.

### 7. События инцидента: `IncidentEventsTable` и `incidentEventColumns.ts`

`PayloadColumn`, `setting`/`measured` и `columnsByType` переезжают в `incidentEventColumns.ts`, оттуда их импортируют и обёртка, и лендинг.

Props `IncidentEventsTable`:
- `events: Array<WatcherIncidentEvent>`;
- `columns: Array<PayloadColumn>`;
- `serviceNames: Record<number, string>`;
- `loading`, `exhausted`, `watcherMissing: boolean`;
- `expandedEventIds: Array<string>`;
- `canOpenInAggregator: boolean` — показывать ли кнопку-фильтр в группах.

События: `load-more`, `expand-change(ids)`, `open-in-aggregator(group, event)`.

Сообщения-заглушки («No events.», «The watcher behind this incident is gone…» и другие) и функции `payloadValue` и `groupsOf` переезжают в таблицу. Если `canOpenInAggregator` выключен, колонка с кнопкой остаётся, но кнопка скрыта через `visibility: hidden`: ширина колонок не меняется.

Обёртка `IncidentEvents` оставляет у себя store, `watcherType`, watch для первой загрузки, `mounted` с загрузкой сервисов, `openInAggregator`, `periodAround` и `lookBack`. `serviceNames` она собирает из `servicesStore.items` и подставляет `Service #id` для отсутствующих, как сейчас.

Типы событий (`WatcherIncidentEvent`, `WatcherIncidentEventGroup`, `*Event`) таблица и модуль колонок импортируют из `incidentsStore.ts` только как типы.

### 8. Функции дат и константа слота без store

`utils/helpers.ts` во время выполнения импортирует store агрегатора (`getPeriodPresetEnumByValue`), поэтому через него в лендинг попал бы store. Чистые функции дат (`normalizeUtcDateTime`, `utcTimestamp`, `makeUtcPickerDate`, `formatUtcDateTime`, `zeroPad` и разбор строки как UTC) переезжают в `utils/utcDateTime.ts` без изменений. `helpers.ts` реэкспортирует их, поэтому существующие импорты не меняются. Компоненты отображения и их модули импортируют даты из `utcDateTime.ts`.

По той же причине `metricSlotMinutes` переезжает из `dashboardMetricsStore.ts` в `traceMetricsValues.ts`. Store импортирует константу оттуда и реэкспортирует её.

### 9. Комментарии при переносе

Комментарий, который объясняет перенесённый код, переезжает вместе с ним. Новых пояснений сверх нужного не добавляется.

## Risks / Trade-offs

- [Строка дерева рисуется сотнями в виртуальном списке, лишняя реактивность на строку заметна на больших деревьях] → Флаги считаются в обёртке теми же выражениями, что сейчас, без новых наблюдателей. `selectedTags` передаётся ссылкой на массив store, без копии.
- [Событие вместо прямого вызова store может потеряться при опечатке в имени] → События объявляются в `emits` у каждого компонента отображения. `vue-tsc` в `npm run build` проверяет типы props и обработчиков.
- [Незаметное изменение вида админки при переносе разметки] → Разметка и стили переносятся без правок. Ручная проверка по чек-листу в tasks.md: дерево (раскрытие, ленивая догрузка, выделение, индикаторы, json, tree), дашборд (Refresh, выбор типов, клик в агрегатор), график агрегатора (live, клик по столбцу), инциденты (раскрытие, «Show more», фильтр в агрегатор).
- [`TraceAggregatorTraceTreeVirtual` без строки по умолчанию ничего не нарисует, если слот не передан] → Единственный потребитель в админке — `TraceAggregatorTraceTree`, и он передаёт слот. Лендинг передаёт свой.
