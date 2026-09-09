<?php

namespace Tests\Modules\Watcher\Domain\Services\Types;

use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\Types\BufferOverflowWatcherType;
use App\Modules\Watcher\Domain\Services\Types\InvalidBufferGrownWatcherType;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\SlowTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\TracesSpikeWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use PHPUnit\Framework\TestCase;

/**
 * The one place a type turns into behaviour. Everything it answers used to be answered in
 * three different classes, two of them in the repository layer.
 */
class WatcherTypeRegistryTest extends TestCase
{
    public function testEveryTypeHasADefinition(): void
    {
        $registry = $this->registry();

        foreach (WatcherTypeEnum::cases() as $type) {
            $this->assertSame($type, $registry->for($type)->describe()->type);
        }
    }

    public function testSettingsSurviveTheRoundTrip(): void
    {
        $settings = new TracesSpikeSettingsObject(
            windowMinutes: 3,
            baselineMinutes: 120,
            growthPercent: 50,
            filter: new \App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject(
                serviceIds: [4],
                types: ['http'],
                tags: ['api']
            )
        );

        $this->assertEquals(
            $settings,
            $this->registry()->for(WatcherTypeEnum::TracesSpike)->makeSettings($settings->toArray())
        );
    }

    /**
     * A settings column written before a field existed is the ordinary case after a
     * release. Refusing to load it would take the watcher out of service over a number
     * that has a perfectly good default.
     */
    public function testAnEmptyColumnLoadsOnDefaults(): void
    {
        $settings = $this->registry()->for(WatcherTypeEnum::SlowTraces)->makeSettings([]);

        $this->assertInstanceOf(SlowTracesSettingsObject::class, $settings);
        $this->assertSame(10.0, $settings->duration);
        $this->assertSame(5, $settings->windowMinutes);
        $this->assertSame([], $settings->traceFilter()->serviceIds);
    }

    /** A filter of the wrong shape is no filter, not a fatal. */
    public function testAMalformedFilterLoadsAsEmpty(): void
    {
        $settings = $this->registry()->for(WatcherTypeEnum::NoNewTraces)->makeSettings(
            ['period_minutes' => 15, 'filter' => 'nonsense']
        );

        $this->assertInstanceOf(NoNewTracesSettingsObject::class, $settings);
        $this->assertSame(15, $settings->periodMinutes);
        $this->assertSame([], $settings->traceFilter()->tags);
    }

    /** The form is built from these, so a type has to say which numbers it takes. */
    public function testEveryTypeDescribesItsFields(): void
    {
        $registry = $this->registry();

        foreach (WatcherTypeEnum::cases() as $type) {
            $this->assertNotEmpty($registry->for($type)->describe()->fields, $type->value);
        }
    }

    /** Only the trace-driven types carry a filter; the buffer ones watch the buffer. */
    public function testOnlyTraceTypesHaveAFilter(): void
    {
        $registry = $this->registry();

        $this->assertFalse($registry->for(WatcherTypeEnum::BufferOverflow)->describe()->hasTraceFilter);
        $this->assertFalse($registry->for(WatcherTypeEnum::InvalidBufferGrown)->describe()->hasTraceFilter);
        $this->assertTrue($registry->for(WatcherTypeEnum::NoNewTraces)->describe()->hasTraceFilter);
        $this->assertTrue($registry->for(WatcherTypeEnum::TracesSpike)->describe()->hasTraceFilter);
        $this->assertTrue($registry->for(WatcherTypeEnum::SlowTraces)->describe()->hasTraceFilter);
    }

    private function registry(): WatcherTypeRegistry
    {
        return new WatcherTypeRegistry(
            new BufferOverflowWatcherType($this->createMock(BufferOverflowChecker::class)),
            new InvalidBufferGrownWatcherType($this->createMock(InvalidBufferGrownChecker::class)),
            new NoNewTracesWatcherType($this->createMock(NoNewTracesChecker::class)),
            new TracesSpikeWatcherType($this->createMock(TracesSpikeChecker::class)),
            new SlowTracesWatcherType($this->createMock(SlowTracesChecker::class))
        );
    }
}
