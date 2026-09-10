<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\RegisterTriggerAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherIncidentEventRepository;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;
use Tests\Modules\Watcher\WatcherFactoryTrait;

/**
 * One problem is one incident, however many times it was noticed; and a problem that
 * lasts an hour must not fill it with sixty identical events.
 */
class RegisterTriggerActionTest extends TestCase
{
    use WatcherTypeRegistryFactoryTrait;

    use WatcherFactoryTrait;

    private const string NOW = '2026-09-07 12:00:00';

    public function testTheFirstTriggerOpensAnIncident(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findLastOpenByWatcherId')->willReturn(null);
        $incidents->expects($this->once())->method('create')->willReturn($this->incident(7));
        $incidents->method('findById')->willReturn($this->incident(7));

        $events = $this->createMock(WatcherIncidentEventRepository::class);
        $events->expects($this->once())->method('create')->with(7);

        $this->register($incidents, $events);
    }

    /** A repeat goes under the incident already open: one row to close, not two. */
    public function testARepeatGoesIntoTheOpenIncident(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findLastOpenByWatcherId')->willReturn($this->incident(7));
        $incidents->expects($this->never())->method('create');
        $incidents->expects($this->once())->method('incrementEventsCount')->with(7);
        $incidents->method('findById')->willReturn($this->incident(7));

        $events = $this->createMock(WatcherIncidentEventRepository::class);
        $events->expects($this->once())->method('create')->with(7);

        $this->register($incidents, $events);
    }

    /**
     * The cooldown is on speaking, not on checking: the pass runs every minute so a
     * problem is noticed promptly, and this is what keeps it from being said every minute.
     */
    public function testNothingIsRecordedInsideTheCooldown(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->expects($this->never())->method('findLastOpenByWatcherId');
        $incidents->expects($this->never())->method('create');

        $events = $this->createMock(WatcherIncidentEventRepository::class);
        $events->expects($this->never())->method('create');

        $this->register(
            $incidents,
            $events,
            lastTriggeredAt: Carbon::parse(self::NOW)->subSeconds(299)
        );
    }

    public function testTheCooldownEndsExactlyWhenItSays(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findLastOpenByWatcherId')->willReturn($this->incident(7));
        $incidents->method('findById')->willReturn($this->incident(7));

        $events = $this->createMock(WatcherIncidentEventRepository::class);
        $events->expects($this->once())->method('create');

        $this->register(
            $incidents,
            $events,
            lastTriggeredAt: Carbon::parse(self::NOW)->subSeconds(300)
        );
    }

    /** A closed incident is history: the next trigger starts a new one. */
    public function testATriggerAfterTheIncidentWasClosedOpensANewOne(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        // Closed incidents are not returned by this lookup, which is what makes the next
        // trigger a new incident rather than a reopening.
        $incidents->method('findLastOpenByWatcherId')->willReturn(null);
        $incidents->expects($this->once())->method('create')->willReturn($this->incident(8));
        $incidents->method('findById')->willReturn($this->incident(8));

        $events = $this->createMock(WatcherIncidentEventRepository::class);
        $events->expects($this->once())->method('create')->with(8);

        $this->register($incidents, $events);
    }

    public function testWhoeverIsWatchingIsToldTheIncidentChanged(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findLastOpenByWatcherId')->willReturn($this->incident(7));
        $incidents->method('findById')->willReturn($this->incident(7, eventsCount: 4));

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                // The row as it now stands, counter and all: the counter was incremented
                // by the database, so a copy patched in memory would be a guess.
                static fn(WatcherIncidentChangedEvent $event): bool => $event->incident->eventsCount === 4
            ));

        $this->register(
            $incidents,
            $this->createMock(WatcherIncidentEventRepository::class),
            dispatcher: $dispatcher
        );
    }

    private function register(
        WatcherIncidentRepository $incidents,
        WatcherIncidentEventRepository $events,
        ?Carbon $lastTriggeredAt = null,
        ?Dispatcher $dispatcher = null
    ): void {
        $now = Carbon::parse(self::NOW);

        new RegisterTriggerAction(
            $this->createMock(WatcherRepository::class),
            $incidents,
            $events,
            $this->watcherTypeRegistry(),
            $dispatcher ?? $this->createMock(Dispatcher::class)
        )->handle(
            $this->watcher(
                WatcherTypeEnum::BufferOverflow,
                new BufferOverflowSettingsObject(),
                lastTriggeredAt: $lastTriggeredAt,
                cooldownSeconds: 300
            ),
            new BufferOverflowEventPayloadObject(
                settings: new BufferOverflowEventSettingsObject(threshold: 1000),
                measured: new BufferOverflowEventMeasuredObject(bufferCount: 5000)
            ),
            $now
        );
    }

    private function incident(int $id, int $eventsCount = 1): WatcherIncidentObject
    {
        $now = Carbon::parse(self::NOW);

        return new WatcherIncidentObject(
            id: $id,
            watcherId: 1,
            status: WatcherIncidentStatusEnum::Opened,
            firstEventAt: $now,
            lastEventAt: $now,
            eventsCount: $eventsCount,
            closedAt: null,
            closedByUserId: null
        );
    }
}
