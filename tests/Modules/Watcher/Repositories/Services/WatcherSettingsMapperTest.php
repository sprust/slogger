<?php

namespace Tests\Modules\Watcher\Repositories\Services;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Services\WatcherSettingsMapper;
use PHPUnit\Framework\TestCase;

class WatcherSettingsMapperTest extends TestCase
{
    public function testSettingsSurviveTheRoundTrip(): void
    {
        $mapper = new WatcherSettingsMapper();

        $settings = new TracesSpikeSettingsObject(
            windowMinutes: 3,
            baselineMinutes: 120,
            growthPercent: 50,
            filter: new WatcherTraceFilterObject(serviceIds: [4], types: ['http'], tags: ['api'])
        );

        $this->assertEquals(
            $settings,
            $mapper->toObject(WatcherTypeEnum::TracesSpike, $mapper->toArray($settings))
        );
    }

    /**
     * A settings column written before a field existed is the ordinary case after a
     * release. Refusing to load it would take the watcher out of service over a number
     * that has a perfectly good default.
     */
    public function testAnEmptyColumnLoadsOnDefaults(): void
    {
        $settings = new WatcherSettingsMapper()->toObject(WatcherTypeEnum::SlowTraces, []);

        $this->assertInstanceOf(SlowTracesSettingsObject::class, $settings);
        $this->assertSame(10.0, $settings->duration);
        $this->assertSame(5, $settings->windowMinutes);
        $this->assertSame([], $settings->traceFilter()->serviceIds);
    }

    /** A filter of the wrong shape is no filter, not a fatal. */
    public function testAMalformedFilterLoadsAsEmpty(): void
    {
        $settings = new WatcherSettingsMapper()->toObject(
            WatcherTypeEnum::NoNewTraces,
            ['period_minutes' => 15, 'filter' => 'nonsense']
        );

        $this->assertInstanceOf(NoNewTracesSettingsObject::class, $settings);
        $this->assertSame(15, $settings->periodMinutes);
        $this->assertSame([], $settings->traceFilter()->tags);
    }

    /** The buffer types carry no filter, and storing one would be a lie to the receiver. */
    public function testBufferSettingsStoreNoFilter(): void
    {
        $stored = new WatcherSettingsMapper()->toArray(new BufferOverflowSettingsObject(threshold: 2000));

        $this->assertSame(['threshold' => 2000], $stored);
    }
}
