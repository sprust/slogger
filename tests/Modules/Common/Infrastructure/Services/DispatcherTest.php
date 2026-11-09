<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use Fiber;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Tests\Modules\Common\Infrastructure\Services\Fixtures\DispatchJob;
use Tests\Modules\Common\Infrastructure\Services\Fixtures\RecordingBusDispatcher;
use Tests\Modules\Common\Infrastructure\Services\Fixtures\RecordingLogger;
use Tests\TestCase;

class DispatcherTest extends TestCase
{
    private RecordingBusDispatcher $recordingBusDispatcher;

    private RecordingLogger $recordingLogger;

    private DispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recordingBusDispatcher = $this->app->make(RecordingBusDispatcher::class);
        $this->recordingLogger        = $this->app->make(RecordingLogger::class);

        $this->app->instance(
            abstract: BusDispatcher::class,
            instance: $this->recordingBusDispatcher,
        );

        $this->app->instance(
            abstract: LoggerInterface::class,
            instance: $this->recordingLogger,
        );

        $this->app->forgetInstance(DispatcherInterface::class);

        $this->dispatcher = $this->app->make(DispatcherInterface::class);

        config(
            key: [
                'queue.default'                      => 'sconcur_rabbitmq',
                'queue.connections.sconcur_rabbitmq' => [
                    'driver' => 'sconcur_rabbitmq',
                    'queue'  => 'default',
                ],
                'sconcur.queue.rabbitmq.connection' => 'sconcur_rabbitmq',
                'sconcur.queue.rabbitmq.queues'     => [
                    'default',
                    'trace-tree',
                ],
                'sconcur.master.groups' => [],
            ],
        );
    }

    /** @return iterable<string, array{string}> */
    public static function cases(): iterable
    {
        foreach (
            [
                'registered',
                'unregistered',
                'duplicate registration',
                'job route override',
                'connection queue fallback',
                'default queue fallback',
                'sync without topology',
                'unknown connection',
                'unknown queue',
                'unsupported async driver',
                'transport failure',
                'parallel dispatch',
                'event identity',
                'listener failure',
                'report original exception',
                'report expected exception',
            ] as $case
        ) {
            yield $case => [
                $case,
            ];
        }
    }

    #[DataProvider('cases')]
    public function test(string $case): void
    {
        if (
            str_starts_with(
                $case,
                'report ',
            )
        ) {
            $exception = $case === 'report expected exception' ? new DatabaseStatCacheNotFoundException() : new RuntimeException(
                message: 'test report',
            );

            $this->dispatcher->report($exception);

            if ($case === 'report expected exception') {
                self::assertCount(
                    0,
                    $this->recordingLogger->records
                );
            } else {
                self::assertSame(
                    $exception,
                    $this->recordingLogger->records[0]['context']['exception']
                );
            }

            return;
        }

        if (
            in_array(
                $case,
                [
                    'event identity',
                    'listener failure',
                ],
                true,
            )
        ) {
            $event     = new stdClass();
            $received  = [];
            $exception = new RuntimeException(
                message: 'listener failed',
            );

            $this->app
                ->make(EventDispatcher::class)
                ->listen(
                    events: stdClass::class,
                    listener: static function (object $event) use (&$received, $case, $exception): void {
                        $received[] = $event;

                        if ($case === 'listener failure') {
                            throw $exception;
                        }
                    },
                );

            if ($case === 'listener failure') {
                $this->expectExceptionObject($exception);
            }

            $this->dispatcher->event($event);

            self::assertSame(
                [
                    $event,
                ],
                $received
            );

            return;
        }

        $dispatchJob = new DispatchJob('secret payload');

        $dispatchJob->delay(45)->afterCommit();

        if ($case !== 'unregistered') {
            $this->dispatcher->registerJobs([
                DispatchJob::class,
            ]);
        }

        switch ($case) {
            case 'duplicate registration':
                $this->dispatcher->registerJobs([
                    DispatchJob::class,
                    DispatchJob::class,
                ]);

                break;
            case 'job route override':
                config(
                    key: [
                        'queue.default' => 'unavailable',
                    ],
                );

                $dispatchJob->onConnection('sconcur_rabbitmq')->onQueue('trace-tree');

                break;
            case 'connection queue fallback':
                config(
                    key: [
                        'queue.connections.sconcur_rabbitmq.queue' => 'trace-tree',
                    ],
                );

                break;
            case 'default queue fallback':
                config(
                    key: [
                        'queue.connections.sconcur_rabbitmq' => [
                            'driver' => 'sconcur_rabbitmq',
                        ],
                    ],
                );

                break;
            case 'sync without topology':
                config(
                    key: [
                        'queue.default'          => 'sync',
                        'sconcur.queue.rabbitmq' => [],
                    ],
                );

                break;
            case 'unknown connection':
                $dispatchJob->onConnection('unknown');

                break;
            case 'unknown queue':
                $dispatchJob->onQueue('unknown');

                break;
            case 'unsupported async driver':
                $dispatchJob->onConnection('redis');

                break;
            case 'transport failure':
                $exception = new RuntimeException(
                    message: 'broker unavailable',
                );
                $this->recordingBusDispatcher->exception = $exception;

                $this->expectExceptionObject($exception);

                break;
            case 'parallel dispatch':
                $this->recordingBusDispatcher->suspend = true;
                $otherDispatchJob                      = new DispatchJob('other payload');

                $otherDispatchJob->onQueue('trace-tree')->delay(90)->beforeCommit();

                $firstFiber  = new Fiber(fn() => $this->dispatcher->dispatch($dispatchJob));
                $secondFiber = new Fiber(fn() => $this->dispatcher->dispatch($otherDispatchJob));

                $firstFiber->start();

                $secondFiber->start();

                $secondFiber->resume();

                $firstFiber->resume();

                self::assertSame(
                    [
                        $otherDispatchJob,
                        $dispatchJob,
                    ],
                    $this->recordingBusDispatcher->jobs
                );

                self::assertSame(
                    'trace-tree',
                    $otherDispatchJob->queue
                );

                self::assertFalse($otherDispatchJob->afterCommit);

                return;
        }

        $this->dispatcher->dispatch($dispatchJob);

        $refused = in_array(
            $case,
            [
                'unregistered',
                'unknown connection',
                'unknown queue',
                'unsupported async driver',
            ],
            true,
        );

        self::assertSame(
            $refused ? [] : [
                $dispatchJob,
            ],
            $this->recordingBusDispatcher->jobs
        );

        self::assertSame(
            45,
            $dispatchJob->delay
        );

        self::assertTrue($dispatchJob->afterCommit);

        self::assertCount(
            $refused ? 1 : 0,
            $this->recordingLogger->records
        );

        self::assertStringNotContainsString(
            'secret payload',
            json_encode(
                $this->recordingLogger->records,
                JSON_THROW_ON_ERROR,
            )
        );
    }
}
