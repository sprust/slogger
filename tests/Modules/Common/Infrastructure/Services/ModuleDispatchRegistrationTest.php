<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Console\Kernel;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptRegistry;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolRegistry;
use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Logs\Infrastructure\Commands\CleanLogsCommand;
use App\Modules\Logs\Infrastructure\Commands\IndexLogsCommand;
use App\Modules\Service\Infrastructure\Commands\CreateServiceCommand;
use App\Modules\User\Infrastructure\Commands\CreateUserCommand;
use App\Modules\User\Infrastructure\Commands\DeleteExpiredUserTokensCommand;
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
    protected bool $fakeEvents = false;

    /** @return iterable<string, array{string}> */
    public static function cases(): iterable
    {
        yield 'registered application jobs' => ['jobs'];

        yield 'single listener registration' => ['listeners'];

        yield 'module commands' => ['commands'];

        yield 'singleton factories' => ['factories'];

        yield 'scheduled application jobs' => ['schedule'];
    }

    #[DataProvider('cases')]
    public function test(
        string $case
    ): void {
        config(
            key: [
                'queue.default'                 => 'sync',
                'module-trace.queue.connection' => 'sync',
                'cleaner.queue.connection'      => 'sync',
            ],
        );

        Queue::fake();

        $dispatcher = $this->app->make(DispatcherInterface::class);

        if ($case === 'commands') {
            $commands = $this->app->make(Kernel::class)->all();

            $commandClasses = [
                'traces-clearing:clear' => ClearTracesCommand::class,
                'logs:index'            => IndexLogsCommand::class,
                'logs:clean'            => CleanLogsCommand::class,
                'service:create'        => CreateServiceCommand::class,
                'user:create'           => CreateUserCommand::class,
                'user:tokens:clear'     => DeleteExpiredUserTokensCommand::class,
            ];

            foreach ($commandClasses as $name => $class) {
                self::assertInstanceOf(
                    $class,
                    $commands[$name]
                );
            }

            return;
        }

        if ($case === 'factories') {
            foreach ([McpToolRegistry::class, McpPromptRegistry::class] as $class) {
                self::assertSame(
                    $this->app->make($class),
                    $this->app->make($class)
                );
            }

            return;
        }

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

            $eventClasses = [
                TraceTreeCacheBuildRequestedEvent::class,
                TraceTreeCacheDeleteRequestedEvent::class,
                TraceTreeCacheStateChangedEvent::class,
                NotificationEnqueuedEvent::class,
                WatcherIncidentChangedEvent::class,
            ];

            foreach ($eventClasses as $eventClass) {
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

            $dispatcher->event(
                new NotificationEnqueuedEvent('notification')
            );

            $jobClasses = [
                BuildTraceTreeCacheJob::class,
                DeleteTraceTreeCacheJob::class,
                SendNotificationJob::class,
            ];

            foreach ($jobClasses as $jobClass) {
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
        $expectedCommands = [
            'user:tokens:clear' => [
                '0 0 * * *',
                false,
            ],
            'logs:index' => [
                '* * * * *',
                true,
            ],
            'logs:clean' => [
                '0 0 * * *',
                true,
            ],
        ];
        $executed          = [];
        $scheduledCommands = [];

        self::assertCount(
            5,
            $schedule->events()
        );

        foreach ($schedule->events() as $event) {
            if (!$event instanceof CallbackEvent) {
                foreach ($expectedCommands as $name => [$expression, $withoutOverlapping]) {
                    if (str_contains($event->command ?? '', $name)) {
                        self::assertSame(
                            $expression,
                            $event->expression
                        );

                        self::assertSame(
                            $withoutOverlapping,
                            $event->withoutOverlapping
                        );

                        $scheduledCommands[] = $name;
                    }
                }
            }

            if (
                !$event instanceof CallbackEvent
                || !array_key_exists($event->description ?? '', $expectedSchedules)
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

        self::assertEqualsCanonicalizing(
            array_keys($expectedSchedules),
            $executed
        );

        self::assertEqualsCanonicalizing(
            array_keys($expectedCommands),
            $scheduledCommands
        );

        foreach (array_keys($expectedSchedules) as $jobClass) {
            Queue::assertPushed(
                $jobClass,
                1
            );
        }
    }
}
