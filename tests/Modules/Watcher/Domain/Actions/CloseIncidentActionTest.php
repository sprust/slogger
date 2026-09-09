<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\CloseIncidentAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Only a person closes an incident, and only once.
 */
class CloseIncidentActionTest extends TestCase
{
    private const string INCIDENT_ID = '68be1f00a1b2c3d4e5f60012';

    public function testClosingRecordsWhoDidIt(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findById')->willReturn($this->incident(WatcherIncidentStatusEnum::Opened));

        $incidents->expects($this->once())
            ->method('updateStatus')
            ->with(
                self::INCIDENT_ID,
                WatcherIncidentStatusEnum::Closed,
                $this->isInstanceOf(Carbon::class),
                7
            );

        $this->close($incidents, closedByUserId: 7);
    }

    /**
     * Two people pressing the button is not an error worth showing either of them, and the
     * one who was first stays on the row.
     */
    public function testAnIncidentAlreadyClosedIsLeftAlone(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findById')->willReturn($this->incident(WatcherIncidentStatusEnum::Closed));
        $incidents->expects($this->never())->method('updateStatus');

        $events = $this->createMock(Dispatcher::class);
        $events->expects($this->never())->method('dispatch');

        new CloseIncidentAction($incidents, $events)->handle(self::INCIDENT_ID, 7);
    }

    /** Whoever is watching is told, so the badge and any open list follow. */
    public function testWhoeverIsWatchingIsTold(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findById')->willReturn($this->incident(WatcherIncidentStatusEnum::Opened));

        $events = $this->createMock(Dispatcher::class);
        $events->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WatcherIncidentChangedEvent::class));

        new CloseIncidentAction($incidents, $events)->handle(self::INCIDENT_ID, 7);
    }

    public function testAnIncidentThatIsNotThereIsReported(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('findById')->willReturn(null);

        $this->expectException(WatcherIncidentNotFoundException::class);

        $this->close($incidents, closedByUserId: 7);
    }

    private function close(WatcherIncidentRepository $incidents, ?int $closedByUserId): void
    {
        new CloseIncidentAction($incidents, $this->createMock(Dispatcher::class))
            ->handle(self::INCIDENT_ID, $closedByUserId);
    }

    private function incident(WatcherIncidentStatusEnum $status): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: self::INCIDENT_ID,
            watcherId: 7,
            status: $status,
            firstEventAt: Carbon::parse('2026-09-08 10:00:00'),
            lastEventAt: Carbon::parse('2026-09-08 10:05:00'),
            eventsCount: 3,
            closedAt: null,
            closedByUserId: null
        );
    }
}
