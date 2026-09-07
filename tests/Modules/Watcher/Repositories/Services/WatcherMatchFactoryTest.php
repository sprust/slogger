<?php

namespace Tests\Modules\Watcher\Repositories\Services;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Repositories\Services\WatcherMatchFactory;
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
        $match = $this->factory()->make(
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
        $this->assertNull($this->factory()->make(new BufferOverflowSettingsObject()));
        $this->assertNull($this->factory()->make(new InvalidBufferGrownSettingsObject()));
    }

    /** An unfiltered watcher counts everything, which is a filter of empty lists — not null. */
    public function testAnEmptyFilterIsStillAMatch(): void
    {
        $match = $this->factory()->make(new NoNewTracesSettingsObject());

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
            $this->factory()->toArray(
                new WatcherMatchObject(serviceIds: [3], types: ['db'], tags: ['slow'])
            )
        );
    }

    public function testNothingToStoreForAWatcherWithoutAMatch(): void
    {
        $this->assertNull($this->factory()->toArray(null));
        $this->assertNull($this->factory()->fromArray(null));
    }

    public function testAStoredMatchReadsBackAsItWasWritten(): void
    {
        $factory = $this->factory();

        $match = $factory->make(
            new TracesSpikeSettingsObject(
                filter: new WatcherTraceFilterObject(serviceIds: [1, 2], types: ['http'], tags: [])
            )
        );

        $restored = $factory->fromArray($factory->toArray($match));

        $this->assertEquals($match, $restored);
    }

    /**
     * The version travels with the value rather than being assumed on read: a match
     * written by a newer panel has to arrive here as its own number, so the receiver —
     * which reads the same column — can refuse what it does not understand.
     */
    public function testAnUnknownVersionIsKeptRatherThanReplaced(): void
    {
        $match = $this->factory()->fromArray([
            'v'           => 99,
            'service_ids' => [],
            'types'       => [],
            'tags'        => [],
        ]);

        $this->assertNotNull($match);
        $this->assertSame(99, $match->version);
    }

    /** A column written before a key existed still has to load. */
    public function testMissingKeysReadAsEmpty(): void
    {
        $match = $this->factory()->fromArray(['v' => 1]);

        $this->assertNotNull($match);
        $this->assertSame([], $match->serviceIds);
        $this->assertSame([], $match->types);
        $this->assertSame([], $match->tags);
    }

    private function factory(): WatcherMatchFactory
    {
        return new WatcherMatchFactory();
    }
}
