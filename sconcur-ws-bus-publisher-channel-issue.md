# «No channel available.» на публикации broadcast'а: канал издателя ws-шины сметает расширение

Repo: https://github.com/sprust/sconcur-laravel

Версия, на которой найдено: `sconcur/laravel` 0.5.1, `sconcur` 0.13.1, RabbitMQ 4.0.

> **Исправлено в `sconcur/laravel` 0.5.2** (коммит `1c0194e`), взято в проект. Вендор
> сделал оба предложенных шага сразу: канал издателя отдаётся, пролежав без дела
> 600 секунд (`MAX_PUBLISHER_IDLE_SECONDS` — тот же запас, что держит
> `PublishChannelPool`), а публикация, упавшая на сохранённом канале, повторяется один
> раз на свежем. Канал при этом отпускается по объекту, чтобы опоздавшая корутина со
> своей ошибкой не закрыла соединение, которое только что открыла соседняя. Ценой стала
> доставка «хотя бы один раз»: повторённая публикация может дойти до брокера дважды —
> для уведомления это дешевле молчания. Наш обходной путь (декоратор
> `RetryingBroadcastBus`, описан ниже) не понадобился и не писался.
>
> Разбор оставлен как есть: он объясняет, почему на проде были эти записи в логе, и
> пригодится, если похожее всплывёт на другом долгоживущем канале.

---

## Что падает

```
#5 Exchange.php(142): Channel->publish(Message, 'slogger.ws', '', false)
#6 vendor/sconcur/laravel/src/Ws/Bus/AmqpBroadcastBus.php(57): Exchange->publish('{"channels":["p...')
#7 SConcurBroadcaster.php(129): AmqpBroadcastBus->publish(BroadcastMessageDto)
…
#31 TraceTreeCacheBuilderService.php(208): AsyncDispatcher->dispatch(TraceTreeCacheStateChangedEvent)
#33 BuildTraceTreeCacheAction.php(52): TraceTreeCacheBuilderService->handleSlice('zhilibyli-…', '01M2Z3FSV1WWHE5…', 0, NULL)
```

Это **издатель** ws-шины, а не доставка джобы. Похожая по тексту ошибка из прошлого
разбора (`.ai/plans/trace-tree-huge.md`, пункт про долгую доставку) шла другим путём —
`AmqpCommandEnum::Ack` из `Job->delete()`, там канал терялся у доставки. Здесь падает
`Publish` в fanout `slogger.ws`, то есть отправка кадра прогресса сборки дерева.

## Почему канал исчезает

`AmqpBroadcastBus` держит соединение и канал издателя вечно: они открываются на первой
публикации (`publisherChannel()`) и складываются в свойства объекта, а объект —
синглтон на процесс. Никакого учёта простоя там нет.

А расширение канал забирает. `docs/amqp.md:866`:

> A channel is closed by `close()`, by the destructor of a dropped `Channel`, when its
> connection dies, or by the sweeper that collects channels with no consumers that have
> run no command for **30 minutes**.

Что это ровно та грабля, вендор знает — в том же пакете, в `PublishChannelPool`, стоит
защита именно от неё:

```php
/**
 * How long a channel may sit unused before the pool gives it up, unless the caller says
 * otherwise. Comfortably under the half hour after which the extension side sweeps a channel
 * that has run no command: a swept channel would surface as a failed publish on the
 * handler unlucky enough to lease it.
 */
protected const float DEFAULT_MAX_IDLE_SECONDS = 600.0;
```

Пул джобной очереди эту границу соблюдает, шина broadcast'ов — нет. Дальше всё сходится:
канал, на котором полчаса не было ни одной команды, сметают; PHP-сторона об этом не
знает и считает его открытым; следующая публикация уходит в ядро и возвращается ошибкой
со scope «канала нет» и кодом 0 — это и есть текст `No channel available.`.

Сигнатура именно канальная, не соединенческая: умри соединение, `translate()` пометил бы
его и поднял `ConnectionException` с другим текстом. То есть сокет жив, heartbeat'ы
ходят, пропал только канал.

## Почему «периодически» и почему на первом кадре сборки

Кадры уходят в шину только когда есть что сообщать: прогресс сборки дерева — раз в
секунду, пока сборка идёт, и полная тишина между сборками. Стоит паузе перевалить за
полчаса — первая же публикация следующей сборки падает.

В приложенном стеке это видно буквально: `handleSlice(…, depth: 0, afterId: NULL)` —
самый первый срез сборки, то есть первый кадр после тишины.

Дальше ошибки нет: `publish()` в своём `catch` сбрасывает издателя (`closePublisher()`) и
пробрасывает исключение, поэтому следующая публикация набирает соединение заново и
проходит. Один кадр теряется, остальная сборка транслируется нормально.

## Чем это грозит

`TraceTreeStateBroadcast` объявлен как `ShouldBroadcastNow, ShouldRescue`, а
`BroadcastManager::queue()` для `ShouldRescue` оборачивает публикацию в `rescue()` —
исключение уходит в обработчик ошибок и не поднимается выше. Поэтому:

- сборка **не** падает, джоба не перезапускается, состояние в Mongo пишется как надо;
- теряется один кадр и появляется запись в логе ошибок.

Терпимо, пока потерянный кадр — очередной тик прогресса: следующий придёт через секунду.
Неприятно, если это единственный кадр события: отмена сборки или очень короткая сборка
после долгой тишины. Тогда панель останется с прежним статусом до перезагрузки страницы.

## Это не таймаут реббита и не длинная джоба

- `consumer_timeout` брокера (30 минут) бьёт по неподтверждённой доставке, а не по
  публикации. Наш прошлый инцидент был именно там (`Ack`), и он вылечен нарезкой сборки
  на короткие джобы (`8a19bee7`).
- Heartbeat (60 с) тоже ни при чём: соединение живо, потерян только канал.
- Длительность джобы роли не играет — падает первый же срез, который живёт секунды.

Совпадение во времени со сборкой дерева обманчиво: сборка не причина, а единственный
регулярный повод что-то опубликовать после долгого молчания.

## Проверка

Скрипт публикует кадр в шину, молчит 31 минуту и публикует снова, в одном процессе
(контейнер воркеров, `BroadcastBusInterface` из контейнера приложения):

```
[09:58:16] первая публикация: ok
[10:29:16] после простоя:    SConcur\Exceptions\Amqp\ChannelException: No channel available.
[10:29:16] сразу следом:     ok
```

Ровно то, что предсказывает механизм: канал, простоявший больше получаса, сметён;
публикация на нём падает; следующая публикация — та же миллисекунда, тот же процесс —
проходит, потому что издатель уже сброшен и набирает соединение заново. Никакого участия
сборки дерева, брокерских таймаутов и длинных джоб здесь нет вовсе.

## Что делать

**Вендору** (`sconcur/laravel`), по убыванию предпочтительности:

1. Одна повторная попытка в `AmqpBroadcastBus::publish()`: сейчас `catch` уже сбрасывает
   издателя, остаётся после сброса попробовать ещё раз — вторая попытка наберёт
   соединение заново. Ровно тот же приём, что описан в `docs/amqp.md` для потребителя:
   «The worker also asks the pool twice before giving up on a channel, because the first
   ask after a connection is lost is the one that discovers it».
2. Либо учёт простоя, как в `PublishChannelPool`: помнить время последней публикации и
   пересоздавать канал, если он простоял дольше ~600 секунд.
3. Либо не держать канал вовсе — открывать его на публикацию. Соединения в расширении
   пулятся по опциям, так что это дешевле, чем кажется.

**Нам, до исправления в пакете** — декоратор над `BroadcastBusInterface` с одной
повторной попыткой:

```php
final readonly class RetryingBroadcastBus implements BroadcastBusInterface
{
    public function __construct(private BroadcastBusInterface $bus) {}

    public function publish(BroadcastMessageDto $message): void
    {
        try {
            $this->bus->publish($message);
        } catch (ChannelException) {
            $this->bus->publish($message);
        }
    }

    public function subscribe(Closure $handler, Closure $shouldContinue): void { … }

    public function needsCoroutine(): bool { … }
}
```

и перерегистрация драйвера `sconcur` в нашем провайдере (пакет регистрирует его через
`$manager->extend('sconcur', …)` внутри `resolving(BroadcastManager::class)`, значит наш
`extend` с тем же именем, выполненный позже, его заменит):

```php
$this->app->resolving(BroadcastManager::class, static function (BroadcastManager $manager): void {
    $manager->extend('sconcur', static fn(Container $app): SConcurBroadcaster => new SConcurBroadcaster(
        bus: new RetryingBroadcastBus($app->make(BroadcastBusInterface::class)),
        verifier: $app->make(SignatureVerifier::class),
    ));
});
```

Порядка сорока строк с тестом, снимается одной строкой, когда пакет починят.

**Чего делать не стоит:**

- переводить `TraceTreeStateBroadcast` на `ShouldBroadcast` — это джоба на каждый кадр,
  то есть лишний круг через тот же пул, от которого в докблоке события сознательно
  ушли;
- греть канал искусственной публикацией по таймеру — у каждого процесса свой издатель, а
  периодического хука внутри пула потребителей нет.
