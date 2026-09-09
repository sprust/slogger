<?php

namespace Tests\Modules\Watcher\Infrastructure\Broadcasting;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherIncidentStatObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Infrastructure\Broadcasting\WatcherIncidentBroadcast;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentStatResource;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * What goes over the wire to the header's badge.
 *
 * A ws payload never reaches the OpenAPI schema, so the panel types the count in this
 * frame as the `data` of the stat endpoint. That only holds while the key matches, and
 * nothing but this test notices when it stops matching.
 */
class WatcherIncidentBroadcastTest extends TestCase
{
    public function testTheFrameNamesTheCountTheWayTheStatEndpointDoes(): void
    {
        $this->assertContains(
            'opened_count',
            array_keys(new WatcherIncidentStatResource(new WatcherIncidentStatObject(3))->toArray())
        );

        $this->assertContains(
            'opened_count',
            array_keys($this->broadcast()->broadcastWith())
        );
    }

    public function testTheFrameGoesToTheOneChannelEveryWatcherShares(): void
    {
        $broadcast = $this->broadcast();

        $this->assertSame(
            ['private-sl-watchers'],
            array_map(strval(...), $broadcast->broadcastOn())
        );

        $this->assertSame('incident.changed', $broadcast->broadcastAs());
    }

    public function testTheFrameSaysWhatChangedAndHowMuchIsLeftOpen(): void
    {
        // Both halves are used: the count is the badge, and the incident is what tells a
        // list that happens to be open which row to look at again.
        $this->assertSame(
            [
                'incident_id'  => '68be1f00a1b2c3d4e5f60012',
                'watcher_id'   => 7,
                'status'       => 'opened',
                'opened_count' => 3,
            ],
            $this->broadcast()->broadcastWith()
        );
    }

    public function testAFailingBusCannotReachTheCheckThatRaisedThis(): void
    {
        // The incident is recorded either way. Without ShouldRescue a bus that is down
        // turns a watcher's check, or a person closing an incident, into a failure.
        $this->assertInstanceOf(ShouldRescue::class, $this->broadcast());
    }

    private function broadcast(): WatcherIncidentBroadcast
    {
        return new WatcherIncidentBroadcast(
            new WatcherIncidentObject(
                id: '68be1f00a1b2c3d4e5f60012',
                watcherId: 7,
                status: WatcherIncidentStatusEnum::Opened,
                firstEventAt: Carbon::parse('2026-09-08 10:00:00'),
                lastEventAt: Carbon::parse('2026-09-08 10:05:00'),
                eventsCount: 2,
                closedAt: null,
                closedByUserId: null
            ),
            openedCount: 3
        );
    }
}
