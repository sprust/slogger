<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Console\Kernel;
use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Dashboard\Infrastructure\Jobs\RefreshDatabaseStatCacheJob;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Infrastructure\Jobs\BuildTraceTreeCacheJob;
use App\Modules\Trace\Infrastructure\Jobs\DeleteTraceTreeCacheJob;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuleDispatchRegistrationTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function cases(): iterable
    {
        yield 'registered application jobs' => [
            'jobs',
        ];

        yield 'single listener registration' => [
            'listeners',
        ];

        yield 'scheduled application jobs' => [
            'schedule',
        ];
    }

    #[DataProvider('cases')]
    public function test(string $case): void
    {
        config(
            key: [
                'queue.default'                 => 'sync',
                'module-trace.queue.connection' => 'sync',
                'cleaner.queue.connection'      => 'sync',
            ],
        );

        Queue::fake();

        $dispatcher = $this->app->make(DispatcherInterface::class);

        if ($case === 'jobs') {
            $jobs = [
                new BuildTraceTreeCacheJob(
                    rootTraceId: 'root',
                    version: 'version',
                    depth: 2,
                    afterId: 'cursor',
                ),
                new DeleteTraceTreeCacheJob(
                    rootTraceId: 'root',
                    buildVersion: 'version',
                ),
                new SendNotificationJob('notification'),
                new ClearTracesJob(),
                new RefreshDatabaseStatCacheJob(),
            ];

            foreach ($jobs as $job) {
                $dispatcher->dispatch($job);

                Queue::assertPushed(
                    $job::class,
                    1
                );
            }

            return;
        }

        if ($case === 'listeners') {
            $listeners = $this->app->make(Dispatcher::class)->getRawListeners();

            foreach (
                [
                    TraceTreeCacheBuildRequestedEvent::class,
                    TraceTreeCacheDeleteRequestedEvent::class,
                    TraceTreeCacheStateChangedEvent::class,
                    NotificationEnqueuedEvent::class,
                    WatcherIncidentChangedEvent::class,
                ] as $eventClass
            ) {
                self::assertCount(
                    1,
                    $listeners[$eventClass],
                    $eventClass
                );
            }

            $dispatcher->event(
                new TraceTreeCacheBuildRequestedEvent(
                    rootTraceId: 'root',
                    version: 'version',
                    depth: 2,
                    afterId: 'cursor',
                )
            );

            $dispatcher->event(
                new TraceTreeCacheDeleteRequestedEvent(
                    rootTraceId: 'root',
                    buildVersion: 'version',
                )
            );

            $dispatcher->event(new NotificationEnqueuedEvent('notification'));

            foreach (
                [
                    BuildTraceTreeCacheJob::class,
                    DeleteTraceTreeCacheJob::class,
                    SendNotificationJob::class,
                ] as $jobClass
            ) {
                Queue::assertPushed(
                    $jobClass,
                    1
                );
            }

            return;
        }

        $schedule          = $this->app->make(Kernel::class)->resolveConsoleSchedule();
        $expectedSchedules = [
            ClearTracesJob::class              => '0 * * * *',
            RefreshDatabaseStatCacheJob::class => '*/5 * * * *',
        ];
        $executed = [];

        foreach ($schedule->events() as $event) {
            if (
                !$event instanceof CallbackEvent || !array_key_exists(
                    $event->description,
                    $expectedSchedules,
                )
            ) {
                continue;
            }

            self::assertSame(
                $expectedSchedules[$event->description],
                $event->expression
            );

            $event->run($this->app);

            $executed[] = $event->description;
        }

        self::assertSame(
            array_keys($expectedSchedules),
            $executed
        );

        foreach (
            array_keys($expectedSchedules) as $jobClass
        ) {
            Queue::assertPushed(
                $jobClass,
                1
            );
        }
    }
}
