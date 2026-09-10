<?php

namespace Tests\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Domain\Services\WatcherFactory;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Dto\WatcherDto;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
/**
 * Where a row becomes a watcher. The repository hands over a type as a string and settings
 * as json; what those mean is decided here and nowhere else.
 */
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;

class WatcherFactoryTest extends TestCase
{
    use WatcherTypeRegistryFactoryTrait;

    public function testTheStoredTypeDecidesHowTheSettingsAreRead(): void
    {
        $watcher = $this->factory()->make(
            $this->dto(
                type: WatcherTypeEnum::SlowTraces->value,
                settings: ['duration' => 15, 'window_minutes' => 3]
            )
        );

        $this->assertSame(WatcherTypeEnum::SlowTraces, $watcher->type);
        $this->assertInstanceOf(SlowTracesSettingsObject::class, $watcher->settings);
        $this->assertSame(15.0, $watcher->settings->duration);
    }

    public function testAStoredMatchReadsBackAsItWasWritten(): void
    {
        $watcher = $this->factory()->make(
            $this->dto(traceMatch: [
                'v'           => 1,
                'service_ids' => [3],
                'types'       => ['http'],
                'tags'        => [],
            ])
        );

        $this->assertEquals(
            new WatcherMatchObject(serviceIds: [3], types: ['http'], tags: []),
            $watcher->match
        );
    }

    public function testAWatcherTheReceiverIgnoresHasNoMatch(): void
    {
        $this->assertNull($this->factory()->make($this->dto(traceMatch: null))->match);
    }

    /**
     * The version travels with the value rather than being assumed on read: a match
     * written by a newer panel has to keep its own number, because the receiver reads the
     * same column and refuses what it does not understand.
     */
    public function testAnUnknownVersionIsKeptRatherThanReplaced(): void
    {
        $watcher = $this->factory()->make($this->dto(traceMatch: ['v' => 99]));

        $this->assertNotNull($watcher->match);
        $this->assertSame(99, $watcher->match->version);
    }

    /** A column written before a key existed still has to load. */
    public function testMissingMatchKeysReadAsEmpty(): void
    {
        $watcher = $this->factory()->make($this->dto(traceMatch: ['v' => 1]));

        $this->assertNotNull($watcher->match);
        $this->assertSame([], $watcher->match->serviceIds);
        $this->assertSame([], $watcher->match->types);
        $this->assertSame([], $watcher->match->tags);
    }

    /**
     * A stored type outlives the code that wrote it — a watcher made by a newer build, or
     * one whose type a rollback took away. Throwing would take out the whole pass, which
     * reads every watcher before it checks any of them.
     */
    public function testARowOfAnUnknownTypeIsSkippedRatherThanFatal(): void
    {
        $this->assertNull($this->factory()->make($this->dto('somethingElse')));
    }

    /**
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $traceMatch
     */
    private function dto(
        string $type = 'slowTraces',
        array $settings = [],
        ?array $traceMatch = null
    ): WatcherDto {
        $now = Carbon::parse('2026-09-07 12:00:00');

        return new WatcherDto(
            id: 1,
            name: 'watcher',
            type: $type,
            enabled: true,
            cooldownSeconds: 300,
            notificationChannelId: null,
            settings: $settings,
            traceMatch: $traceMatch,
            collectSince: null,
            lastCheckedAt: null,
            lastTriggeredAt: null,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function factory(): WatcherFactory
    {
        return new WatcherFactory(
            $this->watcherTypeRegistry(),
            $this->createMock(LoggerInterface::class)
        );
    }
}
