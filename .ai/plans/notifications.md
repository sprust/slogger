# План: каналы уведомлений

Ветка: продолжение `feature/watchers` (или отдельная от неё).

Сверено с кодом: `app/Modules/Watcher/**`, `app/Modules/Dashboard/Domain/Services/SconcurStatClient.php`,
`config/sconcur.php`, `code-analyse/deptrac-layers.yaml`, `composer.json`,
`vendor/sconcur/sconcur/src/Features/HttpClient/**`, `vendor/sconcur/laravel/src/Tasks/**`.

## Цель

Смотритель нашёл проблему — об этом узнают в мессенджере, а не при следующем заходе в
панель. Первый канал — Telegram; остальные (почта, вебхук) добавляются классом, не
переписыванием.

## Что уже готово и чем это ограничено

**Точка расширения есть.** `WatcherIncidentChangedEvent` (`app/Modules/Watcher/Domain/Events`)
диспатчится и при срабатывании (`RegisterTriggerAction`), и при закрытии
(`CloseIncidentAction`). План смотрителей прямо называет её местом для внешних
уведомлений.

**В пуле тасков один воркер.** `config/sconcur.php`, группа `tasks`: `workerCount => 1`.
Блокирующий HTTP-запрос из таска встаёт поперёк всего пула — проверок смотрителей,
`schedule:run`, сборки динамических индексов. Telegram отвечает медленно, а иногда не
отвечает вовсе. Это главное ограничение, из которого следует всё остальное.

**Неблокирующий клиент уже в зависимостях.** `SConcur\Features\HttpClient\HttpClient` —
PSR-18: внутри корутины он её приостанавливает, а не блокирует воркер. Конструктор просит
`ResponseFactoryInterface` (PSR-17) и необязательные `HttpClientOptions` с таймаутами
(`requestTimeoutMs`, `connectTimeoutMs`). Фабрика в проекте уже есть —
`GuzzleHttp\Psr7\HttpFactory` реализует PSR-17. Новых пакетов не нужно.

`SconcurStatClient` ходит блокирующим Guzzle. Это существующее место, к этой задаче
отношения не имеет и здесь не трогается.

**Шаблон «тип — один класс» проверен.** `WatcherTypeRegistry` плюс определение на тип плюс
маршрут на тип. У каналов та же задача: свои настройки, своя форма, свой отправитель.

**Куда класть периодические данные — решено.** Mongo и TTL-индекс, как у инцидентов и
линий смотрителей.

**Адресатов нет.** В `app/Models/Users/User.php` нет ролей, трейсы не делятся по
пользователям. Канал может быть только общим, «отправить вот этому человеку» выразить
нечем.

## Разделение ролей

| Кто | Что делает |
|---|---|
| `Watcher` | находит проблему и поднимает событие; про уведомления не знает ничего |
| `Notification` | слушает событие, составляет текст, складывает в исходящие, отправляет |

Зависимость односторонняя: `Notification` знает про `Watcher`, `Watcher` про
`Notification` — нет. Слушатель лежит в `Notification/Infrastructure/Listeners`, это
`Infrastructure → Domain`, deptrac такое разрешает, а его глобы (`/app/Modules/\w+/...`)
подхватывают новый модуль без правки конфига.

## Схема потока

```mermaid
flowchart TB
    trigger["RegisterTriggerAction / CloseIncidentAction"]
    listener["EnqueueNotificationsListener — составляет текст"]
    channels["notification_channels — MySQL"]
    outbox["notifications — Mongo, TTL 30 дней"]
    task["SendNotificationsTask — пул тасков, раз в 5 секунд"]
    registry["NotificationChannelTypeRegistry"]
    sender["TelegramSender — SConcur HttpClient"]
    telegram["api.telegram.org"]
    panel["ЛК: вкладка Notifications"]

    trigger -->|"WatcherIncidentChangedEvent"| listener
    listener -->|"FindChannelsForWatcherAction"| channels
    listener -->|"по строке на канал"| outbox
    task <-->|"findDue / markSent / markFailed"| outbox
    task -->|"for(type)->sender()"| registry
    registry -->|"send(ChannelObject, NotificationObject)"| sender
    sender <-->|"POST sendMessage"| telegram
    panel -->|"admin-api"| channels
    panel -->|"последние доставки и их ошибки"| outbox
```

## Почему исходящие, а не отправка на месте

Слушатель работает внутри прохода проверок. Синхронный запрос к чужому сервису на этом
пути — то же самое, от чего план смотрителей уводил проверки в пул тасков: подсистема,
которая обязана работать, когда всё плохо, не должна зависеть от того, отвечает ли сейчас
Telegram.

Строка в исходящих переживает перезапуск, даёт повтор без дублей и делает отправку тупой.

**Текст составляется в момент срабатывания, а не в момент отправки.** Сообщение о
вчерашнем инциденте, собранное сегодня, описывало бы сегодняшнее состояние — не то, что
случилось. Поэтому слушатель кладёт в исходящие готовую строку, а отправителю остаётся
доставить её.

## Почему не очередь

`QUEUE_CONNECTION=sconcur_rabbitmq`, и путь джобы — пул тасков → `schedule:run` → RabbitMQ
→ AMQP-пул → джоба. Тот же довод, что и у `CheckWatchersTask`: очередь встала —
уведомления молчат ровно тогда, когда они нужны. Пул тасков и Mongo уже обязаны работать;
ни одной новой зависимости.

## Хранение

### `notification_channels` — MySQL

Единственная таблица модуля. Настройки канала — запись: они живут ровно столько, сколько
живёт канал.

| Колонка | Тип | Смысл |
|---|---|---|
| `id` | bigint | |
| `name` | string(255) | человеческое имя |
| `type` | string(64) | `NotificationChannelTypeEnum` |
| `enabled` | bool, index | выключенный не получает ничего |
| `settings` | text | каст `encrypted:array`: там боевой токен бота |
| `on_opened`, `on_event`, `on_closed` | bool | что именно слать |
| `created_at` / `updated_at` / `deleted_at` | timestamp | мягкое удаление, как у смотрителей |

**Колонки `watcher_ids` пока нет.** Решено начать с «канал получает всё от всех
смотрителей». Когда фильтр понадобится, он появится здесь, а не на смотрителе: смотритель
— про наблюдение, а не про то, кому рассказывать, и добавление канала не должно означать
правку каждого смотрителя.

**`settings` шифруются.** Дамп базы не должен утекать вместе с боевым ботом. Цена
известна: токен нельзя показать обратно в форме — наружу уходит маска, а правка означает
перезапись.

### `notifications` — Mongo, коллекция в `mongodb.traces`

Исходящие. Данные периодические: копятся, пока система работает, и убираются TTL —
`createdAt`, 30 дней, как у инцидентов.

```
{ _id: ObjectId,
  channelId: int,
  watcherId: int,
  incidentId: ObjectId,
  kind: "opened" | "event" | "closed",
  text: string,          // готовый текст, составленный при срабатывании
  createdAt: UTCDateTime,
  attempts: int,
  nextAttemptAt: UTCDateTime,   // когда пробовать в следующий раз
  sentAt: UTCDateTime | null,
  error: string | null }
```

Индексы: TTL на `createdAt`; `{sentAt: 1, nextAttemptAt: 1}` — это и есть выборка задачи;
`{channelId: 1, _id: -1}` — для списка последних доставок в панели.

## Типы каналов

`NotificationChannelTypeDefinitionInterface` — ровно тот же приём, что у смотрителей, и по
той же причине: всё, что зависит от типа, собрано в одном классе, а реестр —
единственный `match` по енуму в модуле.

```php
makeSettings(array $settings): ChannelSettingsInterface   // прочитать колонку
describe(): ChannelTypeObject                             // заголовок, поля, границы — для формы
sender(): NotificationSenderInterface                     // кто отправляет
```

Первый и пока единственный тип — `telegram`. `email` и `webhook` добавляются классом,
реквестом, ресурсом и строкой в реестре.

### Telegram

- `POST https://api.telegram.org/bot<token>/sendMessage`, тело — `chat_id`, `text`,
  `parse_mode: HTML`, `disable_web_page_preview: true`.
- Настройки канала: `bot_token`, `chat_id`. `chat_id` строкой: у групп он отрицательный, у
  каналов бывает `@username`.
- Текст экранируется под HTML: `&`, `<`, `>`. Имя смотрителя приходит от человека.
- Лимит сообщения — 4096 символов; длинное режется с многоточием, а не отправляется в
  отказ.

Разбор ответа:

| Что пришло | Что делаем |
|---|---|
| `200 {"ok":true}` | `sentAt`, строка закрыта |
| `429` с `parameters.retry_after` | ждём ровно столько, сколько просят |
| `400`, `401`, `403` | постоянная ошибка: канал помечается сломанным, повторы прекращаются, причина видна в панели |
| `5xx`, таймаут, обрыв | откат по возрастанию |

## Отправка

`SendNotificationsTask` в том же пуле, в `config/sconcur.php`, `tasks.list`:

```php
[ 'name' => SendNotificationsTask::NAME, 'task' => SendNotificationsTask::class,
  'idle' => 5, 'busy' => 0, 'backoff' => 15 ],
```

`busy => 0` — была работа, значит сразу за следующей пачкой; `idle => 5` — исходящие
обычно пусты, и пять секунд задержки для уведомления ничего не значат.

Тик берёт пачку (не больше 50) строк, у которых `sentAt = null` и `nextAttemptAt <= now`,
и раскладывает их по `SConcur\WaitGroup` — тем же приёмом, что проход смотрителей:

```php
$waitGroup = WaitGroup::create();

foreach ($notifications as $notification) {
    $waitGroup->add(fn() => $this->sendNotificationAction->handle($notification));
}

$waitGroup->waitAll();
```

Клиент неблокирующий, поэтому пятьдесят запросов идут одновременно и медленный Telegram
не держит пул. Один канал не влияет на другие: `SendNotificationAction` ловит и логирует,
пачка идёт дальше.

Откат: 15 с, 1 мин, 5 мин, 15 мин, 1 ч. После шестой попытки строка остаётся с `error` и
больше не берётся — молчащий канал должно быть видно, а не слышно.

## Что отправляется

Три переключателя на канал:

| Событие | По умолчанию | Почему |
|---|---|---|
| инцидент открыт | да | это и есть повод |
| очередное событие в открытом инциденте | нет | cooldown придерживает поток, но чат всё равно жалко |
| инцидент закрыт | да | одна строка, зато видно, что разобрались |

Текст сообщения: имя смотрителя, заголовок его типа, что именно увидели (значение против
порога), время, и — если свёртка есть — самый заметный вид трейсов с его `trace_id`.

## Структура модуля

```text
app/Modules/Notification/
├── Enums/
│   └── NotificationChannelTypeEnum.php
├── Entities/
│   ├── ChannelObject.php
│   ├── ChannelTypeObject.php
│   ├── NotificationObject.php            — строка исходящих
│   ├── SendResultObject.php              — доставлено / подождать столько-то / отказ навсегда
│   └── Settings/
│       ├── ChannelSettingsInterface.php
│       └── TelegramSettingsObject.php
├── Parameters/
│   ├── CreateChannelParameters.php
│   ├── UpdateChannelParameters.php
│   └── FindNotificationsParameters.php
├── Repositories/
│   ├── Dto/ChannelDto.php
│   ├── ChannelRepository.php             — MySQL
│   └── NotificationRepository.php        — Mongo: findDue, create, markSent, markFailed
├── Domain/
│   ├── Actions/Mutations/
│   │   ├── CreateChannelAction.php
│   │   ├── UpdateChannelAction.php
│   │   ├── DeleteChannelAction.php
│   │   ├── EnqueueNotificationAction.php — составить текст и разложить по каналам
│   │   ├── SendNotificationAction.php    — одна строка, одна попытка
│   │   └── SendTestNotificationAction.php
│   ├── Actions/Queries/
│   │   ├── FindChannelsAction.php
│   │   ├── FindChannelAction.php
│   │   ├── FindChannelTypesAction.php
│   │   ├── FindChannelsForWatcherAction.php
│   │   └── FindNotificationsAction.php   — последние доставки канала
│   ├── Services/Types/
│   │   ├── NotificationChannelTypeDefinitionInterface.php
│   │   ├── TelegramChannelType.php
│   │   └── NotificationChannelTypeRegistry.php
│   ├── Services/Senders/
│   │   ├── NotificationSenderInterface.php
│   │   └── TelegramSender.php
│   ├── Services/ChannelFactory.php       — ChannelDto -> ChannelObject
│   └── Services/IncidentMessageFactory.php — событие смотрителя -> текст
└── Infrastructure/
    ├── Tasks/SendNotificationsTask.php
    ├── Listeners/EnqueueNotificationsListener.php
    ├── Http/Controllers/NotificationChannelController.php      — список, типы, удаление, тест
    ├── Http/Controllers/TelegramChannelController.php          — создание и правка
    ├── Http/Controllers/AbstractChannelTypeController.php
    ├── Http/Requests/CreateTelegramChannelRequest.php          — токен обязателен
    ├── Http/Requests/UpdateTelegramChannelRequest.php          — токен необязателен
    ├── Http/Resources/ChannelResource.php                      — без настроек
    ├── Http/Resources/TelegramChannelSettingsResource.php      — токен маской
    ├── Http/Resources/ChannelTypeResource.php
    ├── Http/Resources/NotificationResource.php
    └── NotificationServiceProvider.php
```

Модели: `app/Models/Notifications/NotificationChannel.php` (MySQL, `AbstractModel`,
`SoftDeletes`) и `Notification.php` (Mongo, `AbstractTraceModel`).

## HTTP API

В `routes/admin-api.php`, тем же приёмом, что у смотрителей — маршрут на тип, потому что
тело зависит от типа:

```php
Route::prefix('/notification-channels')->as('notification-channels.')->group(function () {
    Route::get('', [NotificationChannelController::class, 'index'])->name('index');
    Route::get('/types', [NotificationChannelController::class, 'types'])->name('types');
    Route::get('/{id}/deliveries', [NotificationChannelController::class, 'deliveries'])->name('deliveries');
    Route::post('/{id}/test', [NotificationChannelController::class, 'test'])->name('test');
    Route::delete('/{id}', [NotificationChannelController::class, 'delete'])->name('delete');

    // GET /notification-channels/telegram/{id}
    // POST /notification-channels/telegram
    // PATCH /notification-channels/telegram/{id}
});
```

`test` отправляет пробное сообщение прямо в запросе и отвечает результатом: канал, который
молчит, иначе не отличить от тишины по делу. Это единственное место, где отправка
синхронна — HTTP-воркеров два, и запрос делает именно то, о чём его попросили.

После изменений — `make oa-generate`, затем `make frontend-npm-build`.

## Фронт

Вкладка `Notifications` в шапке после `Watchers`, маршрут `/notifications`.

- Таблица каналов: имя, тип, включён, что слать, последняя ошибка.
- Диалог создания и правки: поля из `/notification-channels/types`, как у смотрителей.
  Токен приходит маской и записывается только если его тронули.
- Кнопка «Отправить тест» на строке, с результатом.
- Раскрытие строки — последние доставки: время, инцидент, статус, ошибка.

## Тесты

- `TelegramSenderTest` — разбор ответов: успех, `429` с `retry_after`, `400` как
  постоянный отказ, `5xx` как временный; экранирование HTML; обрезка длинного текста.
- `IncidentMessageFactoryTest` — текст для каждого типа смотрителя, включая тот, у
  которого нет свёртки.
- `EnqueueNotificationsListenerTest` — фильтр по смотрителям, три переключателя,
  выключенный канал не получает строки.
- `SendNotificationsTaskTest` — берётся только то, чей срок подошёл; отказ одного канала
  не уносит пачку; откат растёт; после последней попытки строка больше не берётся.
- `ChannelFactoryTest` — колонка настроек читается по типу, неизвестный тип пропускается,
  как в `WatcherFactory`.

## Этапы

1. ~~**Каналы.** Таблица, модель, реестр типов, `TelegramSender`, маршрут теста.
   Проверяется кнопкой «тест» без единого смотрителя.~~ Сделано: модуль
   `app/Modules/Notification`, миграция `2026_09_08_182727_create_notification_channels_table`,
   маршруты `/notification-channels`, тесты в `tests/Modules/Notification`.
2. **Исходящие.** Коллекция с TTL, репозиторий, `SendNotificationsTask`, повторы и откаты.
3. **Связь со смотрителями.** Слушатель, `IncidentMessageFactory`, три переключателя.
4. **Фронт.** Вкладка, форма, доставки, `make oa-generate` и `make frontend-npm-build`.

Этапы 1–3 самостоятельны и проверяются без фронта.

## Принятые решения

1. Отдельный модуль `Notification`; зависимость односторонняя.
2. Исходящие в Mongo с TTL 30 дней, текст составляется при срабатывании.
3. Отправляет задача пула, а не джоба: уведомления не должны зависеть от очереди.
4. HTTP — неблокирующим клиентом SConcur, потому что воркер тасков один.
5. Фильтр по смотрителям — на канале, но не сейчас: этап 1 сделан без `watcher_ids`.
6. `settings` шифруются, наружу токен уходит маской. Пустой токен в PATCH означает
   «оставить сохранённый» — форме нечего прислать обратно, кроме маски.
7. Тип канала — один класс плюс строка в реестре, как у смотрителей.
8. Повторные события (`on_event`) по умолчанию выключены: cooldown придерживает поток,
   но чат всё равно жалко.

## Вне рамок

- Почта и вебхуки: тот же реестр, отдельные типы, позже.
- Адресация конкретным людям: ролей и адресатов в модели пользователя нет.
- Расписание тишины («не будить ночью»): осмысленно, но это отдельный разговор про часовые
  пояса.
- Уведомления не от смотрителей (упавшая чистка, сломанный индекс): тот же слушатель на
  другое событие, но сначала нужен повод.

## Требуют решения

1. **Срок хранения исходящих — 30 дней**, как у инцидентов. Доставки нужны для разбора
   «почему молчало», и дольше месяца это едва ли кому-то интересно. Решается на этапе 2.
