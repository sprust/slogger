<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\UpdateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Events\NoNewTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Domain\Services\WatcherCollectionStart;
use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Parameters\UpdateWatcherParameters;
use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

/**
 * When an edit makes the line already collected unfit to answer with, and what happens to
 * it then.
 */
class UpdateWatcherActionTest extends TestCase
{
    use WatcherFactoryTrait;

    private const string COLLECTING_SINCE = '2026-09-06 12:00:00';

    /**
     * A window that grew reaches back further than the line does. The trimming has been
     * cutting it to the old depth all along, so the stretch before the change was never
     * kept — and a checker reading it would take what is missing for an absence: a
     * no_new_traces watcher moved from ten minutes to an hour would report an hour of
     * silence about sixteen minutes after the edit.
     */
    public function testAWindowThatGrewStartsCollectingAgain(): void
    {
        $this->assertCollectSinceIsNow(
            stored: new NoNewTracesSettingsObject(periodMinutes: 10),
            edited: ['period_minutes' => 60]
        );
    }

    /** A shorter window is answered by the line as it stands. */
    public function testAWindowThatShrankKeepsTheLine(): void
    {
        $this->assertCollectSinceIsKept(
            stored: new NoNewTracesSettingsObject(periodMinutes: 60),
            edited: ['period_minutes' => 10]
        );
    }

    /** A rename, a new threshold, the same window: the line still answers the question. */
    public function testAnEditThatChangesNothingAboutTheWindowKeepsTheLine(): void
    {
        $this->assertCollectSinceIsKept(
            stored: new NoNewTracesSettingsObject(periodMinutes: 10),
            edited: ['period_minutes' => 10]
        );
    }

    /**
     * A changed filter makes the line answer a different question, so it goes and the
     * watcher starts again.
     */
    public function testAChangedFilterThrowsTheLineAway(): void
    {
        $timelines = $this->createMock(WatcherTimelineRepository::class);
        $timelines->expects($this->once())->method('delete')->with(1);

        $this->assertCollectSinceIsNow(
            stored: new NoNewTracesSettingsObject(periodMinutes: 10),
            edited: ['period_minutes' => 10, 'filter' => ['service_ids' => [3]]],
            timelines: $timelines
        );
    }

    /** The same filter written differently is the same filter. */
    public function testTheSameFilterInAnotherOrderIsNotAChange(): void
    {
        $timelines = $this->createMock(WatcherTimelineRepository::class);
        $timelines->expects($this->never())->method('delete');

        $this->assertCollectSinceIsKept(
            stored: new NoNewTracesSettingsObject(
                periodMinutes: 10,
                filter: new WatcherTraceFilterObject(serviceIds: [3, 7], types: ['http'])
            ),
            edited: [
                'period_minutes' => 10,
                'filter'         => ['service_ids' => [7, 3], 'types' => ['http']],
            ],
            timelines: $timelines
        );
    }

    /**
     * Switched off and on again: nothing was collected while it was off, and the gap would
     * read as silence.
     */
    public function testSwitchingAWatcherBackOnStartsCollectingAgain(): void
    {
        $this->assertCollectSinceIsNow(
            stored: new NoNewTracesSettingsObject(periodMinutes: 10),
            edited: ['period_minutes' => 10],
            wasEnabled: false
        );
    }

    /**
     * @param array<string, mixed> $edited
     */
    private function assertCollectSinceIsNow(
        NoNewTracesSettingsObject $stored,
        array $edited,
        ?WatcherTimelineRepository $timelines = null,
        bool $wasEnabled = true
    ): void {
        $collectSince = $this->update($stored, $edited, $timelines, $wasEnabled);

        $this->assertNotNull($collectSince);
        $this->assertNotSame(self::COLLECTING_SINCE, $collectSince->toDateTimeString());
    }

    /**
     * @param array<string, mixed> $edited
     */
    private function assertCollectSinceIsKept(
        NoNewTracesSettingsObject $stored,
        array $edited,
        ?WatcherTimelineRepository $timelines = null,
        bool $wasEnabled = true
    ): void {
        $collectSince = $this->update($stored, $edited, $timelines, $wasEnabled);

        $this->assertNotNull($collectSince);
        $this->assertSame(self::COLLECTING_SINCE, $collectSince->toDateTimeString());
    }

    /**
     * @param array<string, mixed> $edited
     */
    private function update(
        NoNewTracesSettingsObject $stored,
        array $edited,
        ?WatcherTimelineRepository $timelines,
        bool $wasEnabled
    ): ?Carbon {
        $watcher = $this->watcherWith($stored, $wasEnabled);

        $written = null;

        $watchers = $this->createMock(WatcherRepository::class);
        $watchers->expects($this->once())
            ->method('update')
            // Named, not indexed: the repository's signature grows.
            ->willReturnCallback(function (
                int $id,
                string $name,
                bool $enabled,
                int $cooldownSeconds,
                ?int $notificationChannelId,
                array $settings,
                ?array $traceMatch,
                ?Carbon $collectSince
            ) use (&$written): void {
                $written = $collectSince;
            });

        $find = $this->createMock(FindWatcherAction::class);
        $find->method('handle')->willReturn($watcher);

        new UpdateWatcherAction(
            $watchers,
            $timelines ?? $this->createMock(WatcherTimelineRepository::class),
            $this->registry(),
            new WatcherMatchFactory(),
            $find,
            new WatcherCollectionStart()
        )->handle(
            new UpdateWatcherParameters(
                id: 1,
                name: 'watcher',
                enabled: true,
                cooldownSeconds: 600,
                notificationChannelId: null,
                settings: $edited
            )
        );

        return $written;
    }

    private function watcherWith(NoNewTracesSettingsObject $settings, bool $enabled): WatcherObject
    {
        $watcher = $this->watcher(
            WatcherTypeEnum::NoNewTraces,
            $settings,
            collectSince: Carbon::parse(self::COLLECTING_SINCE)
        );

        return new WatcherObject(
            id: $watcher->id,
            name: $watcher->name,
            type: $watcher->type,
            enabled: $enabled,
            cooldownSeconds: $watcher->cooldownSeconds,
            notificationChannelId: $watcher->notificationChannelId,
            settings: $watcher->settings,
            match: new WatcherMatchFactory()->make($settings),
            collectSince: $watcher->collectSince,
            lastCheckedAt: $watcher->lastCheckedAt,
            lastTriggeredAt: $watcher->lastTriggeredAt,
            createdAt: $watcher->createdAt,
            updatedAt: $watcher->updatedAt
        );
    }

    private function registry(): WatcherTypeRegistry
    {
        $registry = $this->createMock(WatcherTypeRegistry::class);

        $registry->method('for')->willReturn(
            new NoNewTracesWatcherType(
                $this->createMock(NoNewTracesChecker::class),
                new NoNewTracesEventPayloadMapper()
            )
        );

        return $registry;
    }
}
