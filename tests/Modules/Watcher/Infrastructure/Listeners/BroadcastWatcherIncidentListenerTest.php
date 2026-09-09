<?php

namespace Tests\Modules\Watcher\Infrastructure\Listeners;

use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentStatAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherIncidentStatObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Infrastructure\Broadcasting\WatcherIncidentBroadcast;
use App\Modules\Watcher\Infrastructure\Listeners\BroadcastWatcherIncidentListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * What the header's badge is told, and where the number in it comes from.
 */
class BroadcastWatcherIncidentListenerTest extends TestCase
{
    public function testTheFrameCarriesTheCountAsItStandsAfterTheChange(): void
    {
        $published = $this->publish($this->incident(WatcherIncidentStatusEnum::Opened), openedCount: 4);

        // Read from the table by the listener rather than carried by the domain event:
        // how many incidents stand open is a reading, not part of what happened to this
        // one. A closing that takes the count to zero has to say zero.
        $this->assertSame(4, $published->broadcastWith()['opened_count']);
    }

    public function testTheFrameSaysWhichIncidentMoved(): void
    {
        $published = $this->publish($this->incident(WatcherIncidentStatusEnum::Closed), openedCount: 0);

        // The badge only needs the count; this half is what tells a list that happens to
        // be open which row to read again.
        $this->assertSame(
            ['incident_id' => '68be1f00a1b2c3d4e5f60012', 'watcher_id' => 7, 'status' => 'closed', 'opened_count' => 0],
            $published->broadcastWith()
        );
    }

    private function publish(WatcherIncidentObject $incident, int $openedCount): WatcherIncidentBroadcast
    {
        $stat = $this->createMock(FindIncidentStatAction::class);
        $stat->method('handle')->willReturn(new WatcherIncidentStatObject($openedCount));

        $published = null;

        $events = $this->createMock(Dispatcher::class);
        $events->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (mixed $broadcast) use (&$published) {
                $published = $broadcast;

                return [];
            });

        new BroadcastWatcherIncidentListener($stat, $events)
            ->handle(new WatcherIncidentChangedEvent($incident));

        $this->assertInstanceOf(WatcherIncidentBroadcast::class, $published);

        return $published;
    }

    private function incident(WatcherIncidentStatusEnum $status): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: '68be1f00a1b2c3d4e5f60012',
            watcherId: 7,
            status: $status,
            firstEventAt: Carbon::parse('2026-09-08 10:00:00'),
            lastEventAt: Carbon::parse('2026-09-08 10:05:00'),
            eventsCount: 3,
            closedAt: $status === WatcherIncidentStatusEnum::Closed
                ? Carbon::parse('2026-09-08 10:06:00')
                : null,
            closedByUserId: null
        );
    }
}
