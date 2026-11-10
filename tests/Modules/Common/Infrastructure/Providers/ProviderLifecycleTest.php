<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Providers;

use App\Modules\Cleaner\Infrastructure\CleanerServiceProvider;
use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Common\Infrastructure\CommonServiceProvider;
use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagers;
use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Infrastructure\Jobs\BuildTraceTreeCacheJob;
use App\Modules\Trace\Infrastructure\Jobs\DeleteTraceTreeCacheJob;
use App\Modules\Trace\Infrastructure\TraceServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Common\Infrastructure\Providers\Fixtures\RecordingRegistrationDispatcher;

class ProviderLifecycleTest extends TestCase
{
    /** @return iterable<string, array{bool}> */
    public static function environments(): iterable
    {
        yield 'console registers module commands' => [true];

        yield 'http does not register console commands' => [false];
    }

    /**
     * @throws BindingResolutionException
     */
    #[DataProvider('environments')]
    public function testRegistrationIsLocalToEachApplication(
        bool $console
    ): void {
        $previousContainer = Container::getInstance();
        $recorders         = [];

        try {
            for ($index = 0; $index < 2; $index++) {
                $application = new class($console) extends Application {
                    public function __construct(private readonly bool $console)
                    {
                        parent::__construct();
                    }

                    public function runningInConsole(): bool
                    {
                        return $this->console;
                    }
                };
                $commonServiceProvider  = new CommonServiceProvider($application);
                $traceServiceProvider   = new TraceServiceProvider($application);
                $cleanerServiceProvider = new class($application) extends CleanerServiceProvider {
                    /** @var list<class-string> */
                    public array $registeredCommands = [];

                    public function commands($commands): void
                    {
                        $this->registeredCommands = $commands;
                    }
                };

                $commonServiceProvider->register();
                $traceServiceProvider->register();
                $cleanerServiceProvider->register();

                self::assertTrue(
                    $application->isShared(DispatcherInterface::class)
                );
                self::assertTrue(
                    $application->isShared(TraceFieldTitlesService::class)
                );

                $recordingRegistrationDispatcher = new RecordingRegistrationDispatcher();
                $recorders[]                     = $recordingRegistrationDispatcher;
                $eventDispatcher                 = new Dispatcher($application);

                $application->instance(
                    abstract: DispatcherInterface::class,
                    instance: $recordingRegistrationDispatcher,
                );

                $application->instance(
                    abstract: EventDispatcher::class,
                    instance: $eventDispatcher,
                );

                $commonServiceProvider->boot();
                $traceServiceProvider->boot();
                $cleanerServiceProvider->boot();

                self::assertSame(
                    $console ? [ClearTracesCommand::class] : [],
                    $cleanerServiceProvider->registeredCommands
                );

                self::assertCount(
                    3,
                    $eventDispatcher->getRawListeners()
                );

                $exceptionManagers        = $application->make(ExceptionManagers::class);
                $mappedException          = $exceptionManagers->makeJsonHttpResponse(
                    new TraceNotFoundException('trace not found')
                );

                self::assertNotNull(
                    $mappedException
                );
                self::assertSame(
                    404,
                    $mappedException->httpStatus
                );
            }

            foreach ($recorders as $recordingRegistrationDispatcher) {
                self::assertSame(
                    [
                        BuildTraceTreeCacheJob::class,
                        DeleteTraceTreeCacheJob::class,
                        ClearTracesJob::class,
                    ],
                    $recordingRegistrationDispatcher->jobs
                );
            }
        } finally {
            Container::setInstance($previousContainer);
        }
    }
}
