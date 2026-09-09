<?php

namespace Tests\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use PHPUnit\Framework\TestCase;

/**
 * `trace_match` is the whole of what the Go receiver knows about watchers, so what this
 * class writes is a cross-service contract rather than an internal detail. The shape is
 * asserted key by key on purpose: a renamed key here is a receiver that silently counts
 * nothing.
 */
class WatcherMatchFactoryTest extends TestCase
{
    public function testSettingsThatDescribeTracesBecomeAMatch(): void
    {
        $match = new WatcherMatchFactory()->make(
            new SlowTracesSettingsObject(
                duration: 10,
                filter: new WatcherTraceFilterObject(
                    serviceIds: [3, 7],
                    types: ['http'],
                    tags: ['billing']
                )
            )
        );

        $this->assertNotNull($match);
        $this->assertSame([3, 7], $match->serviceIds);
        $this->assertSame(['http'], $match->types);
        $this->assertSame(['billing'], $match->tags);
        $this->assertSame(WatcherMatchObject::VERSION, $match->version);
    }

    /**
     * The buffer watchers are about the buffer, not about traces. A match for them would
     * make the receiver load and evaluate a watcher whose answer it can never produce.
     */
    public function testBufferSettingsHaveNoMatch(): void
    {
        $factory = new WatcherMatchFactory();

        $this->assertNull($factory->make(new BufferOverflowSettingsObject()));
        $this->assertNull($factory->make(new InvalidBufferGrownSettingsObject()));
    }

    /** An unfiltered watcher counts everything, which is a filter of empty lists — not null. */
    public function testAnEmptyFilterIsStillAMatch(): void
    {
        $match = new WatcherMatchFactory()->make(new NoNewTracesSettingsObject());

        $this->assertNotNull($match);
        $this->assertSame([], $match->serviceIds);
        $this->assertSame([], $match->types);
        $this->assertSame([], $match->tags);
    }

    public function testTheStoredShapeIsTheOneTheReceiverReads(): void
    {
        $this->assertSame(
            [
                'v'           => 1,
                'service_ids' => [3],
                'types'       => ['db'],
                'tags'        => ['slow'],
            ],
            new WatcherMatchFactory()->toArray(
                new WatcherMatchObject(serviceIds: [3], types: ['db'], tags: ['slow'])
            )
        );
    }

    public function testNothingToStoreForAWatcherWithoutAMatch(): void
    {
        $this->assertNull(new WatcherMatchFactory()->toArray(null));
    }
}
