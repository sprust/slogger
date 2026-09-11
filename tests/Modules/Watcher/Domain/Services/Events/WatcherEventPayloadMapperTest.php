<?php

namespace Tests\Modules\Watcher\Domain\Services\Events;

use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventPayloadObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventSettingsObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventSettingsObject;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use LogicException;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;

/**
 * The mappers hold the only copy of the stored key names, in both directions. What these
 * cases check is that the two directions agree — a key renamed on the way out and not on
 * the way in is the failure they exist for.
 *
 * The round trip walks WatcherTypeEnum::cases(), so a type added without a payload of its
 * own fails here rather than on the first incident of that type.
 */
class WatcherEventPayloadMapperTest extends TestCase
{
    use WatcherTypeRegistryFactoryTrait;

    public function testEveryTypeSurvivesTheRoundTrip(): void
    {
        foreach (WatcherTypeEnum::cases() as $type) {
            $mapper = $this->watcherTypeRegistry()->for($type)->eventPayloadMapper();

            $payload = $this->payloadOf($type);

            $this->assertEquals(
                $payload,
                $mapper->read($mapper->toDocument($payload)),
                "$type->value did not survive the round trip"
            );
        }
    }

    /**
     * A document from before a number existed. It reads as nothing rather than as a
     * payload with a hole in it — the event keeps its place, and the panel shows it with
     * no numbers.
     */
    public function testAPayloadMissingANumberReadsAsNothing(): void
    {
        $mapper = $this->watcherTypeRegistry()->for(WatcherTypeEnum::SlowTraces)->eventPayloadMapper();

        $document = $mapper->toDocument($this->payloadOf(WatcherTypeEnum::SlowTraces));

        unset($document['settings']['window_minutes']);

        $this->assertNull($mapper->read($document));
    }

    public function testAPayloadOfAnotherTypeIsRefused(): void
    {
        $mapper = $this->watcherTypeRegistry()->for(WatcherTypeEnum::BufferOverflow)->eventPayloadMapper();

        $this->expectException(LogicException::class);

        $mapper->toDocument($this->payloadOf(WatcherTypeEnum::SlowTraces));
    }

    /** A group nobody can read costs a line in the breakdown, not the whole event. */
    public function testAGroupMissingWhatAGroupIsMadeOfIsDropped(): void
    {
        $mapper = $this->watcherTypeRegistry()->for(WatcherTypeEnum::ManyTraces)->eventPayloadMapper();

        $document = $mapper->toDocument($this->payloadOf(WatcherTypeEnum::ManyTraces));

        $document['groups'][] = ['type' => 'job', 'tags' => [], 'count' => 4];

        $read = $mapper->read($document);

        $this->assertInstanceOf(ManyTracesEventPayloadObject::class, $read);
        $this->assertCount(1, $read->groups);
        $this->assertSame('http', $read->groups[0]->type);
    }

    private function payloadOf(WatcherTypeEnum $type): WatcherEventPayloadInterface
    {
        return match ($type) {
            WatcherTypeEnum::BufferOverflow => new BufferOverflowEventPayloadObject(
                settings: new BufferOverflowEventSettingsObject(threshold: 1000),
                measured: new BufferOverflowEventMeasuredObject(bufferCount: 12000)
            ),
            WatcherTypeEnum::InvalidBufferGrown => new InvalidBufferGrownEventPayloadObject(
                settings: new InvalidBufferGrownEventSettingsObject(threshold: 1),
                measured: new InvalidBufferGrownEventMeasuredObject(
                    invalidCount: 7,
                    since: '2026-09-08 19:00:00'
                )
            ),
            WatcherTypeEnum::NoNewTraces => new NoNewTracesEventPayloadObject(
                settings: new NoNewTracesEventSettingsObject(periodMinutes: 10),
                measured: new NoNewTracesEventMeasuredObject(
                    windowFrom: '2026-09-08 19:00:00',
                    windowTo: '2026-09-08 19:10:00'
                )
            ),
            WatcherTypeEnum::ManyTraces => new ManyTracesEventPayloadObject(
                settings: new ManyTracesEventSettingsObject(windowMinutes: 5, threshold: 1000),
                measured: new ManyTracesEventMeasuredObject(windowCount: 1200),
                groups: [$this->group()]
            ),
            WatcherTypeEnum::SlowTraces => new SlowTracesEventPayloadObject(
                settings: new SlowTracesEventSettingsObject(duration: 10.0, windowMinutes: 5),
                measured: new SlowTracesEventMeasuredObject(slowest: 41.2),
                groups: [$this->group(durationMax: 41.2, slowestTraceId: 'abc123')]
            ),
            WatcherTypeEnum::LogErrors => new LogErrorsEventPayloadObject(
                settings: new LogErrorsEventSettingsObject(threshold: 1),
                measured: new LogErrorsEventMeasuredObject(
                    errorCount: 3,
                    since: '2026-09-08 19:00:00',
                    lastMessage: 'connect: IO error: Connection refused (os error 111)'
                )
            ),
        };
    }

    private function group(
        ?float $durationMax = null,
        ?string $slowestTraceId = null
    ): WatcherIncidentEventGroupObject {
        return new WatcherIncidentEventGroupObject(
            serviceId: 7,
            type: 'http',
            tags: ['api'],
            count: 3,
            durationMax: $durationMax,
            slowestTraceId: $slowestTraceId
        );
    }
}
