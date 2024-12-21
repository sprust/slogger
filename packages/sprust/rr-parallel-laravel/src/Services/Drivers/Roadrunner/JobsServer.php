<?php

namespace RrParallel\Services\Drivers\Roadrunner;

use Illuminate\Foundation\Application;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\DispatchesEvents;
use Psr\SimpleCache\InvalidArgumentException;
use RrParallel\Events\JobHandledEvent;
use RrParallel\Events\JobHandlingErrorEvent;
use RrParallel\Events\JobReceivedEvent;
use RrParallel\Events\JobWaitingErrorEvent;
use RrParallel\Events\WorkerServeErrorEvent;
use RrParallel\Events\WorkerStartingEvent;
use RrParallel\Events\WorkerStoppedEvent;
use RrParallel\Services\Dto\JobResultDto;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Worker;
use Throwable;

readonly class JobsServer
{
    use DispatchesEvents;

    private ParallelJobSerializer $jobSerializer;

    public function __construct(
        private Application $app,
        private Worker $worker
    ) {
        $this->jobSerializer = $app->make(ParallelJobSerializer::class);
    }

    public function serve(): void
    {
        $this->dispatchEvent(
            app: $this->app,
            event: new WorkerStartingEvent($this->app)
        );

        try {
            $this->onServe();
        } catch (Throwable $exception) {
            $this->dispatchEvent(
                app: $this->app,
                event: new WorkerServeErrorEvent(
                    app: $this->app,
                    exception: $exception
                )
            );

            return;
        }

        $this->dispatchEvent(
            app: $this->app,
            event: new WorkerStoppedEvent($this->app)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function onServe(): void
    {
        $consumer = new Consumer($this->worker);

        while (true) {
            try {
                $task = $consumer->waitTask();
            } catch (Throwable $exception) {
                $this->dispatchEvent(
                    app: $this->app,
                    event: new JobWaitingErrorEvent($exception)
                );

                break;
            }

            if (!$task) {
                break;
            }

            $app = clone $this->app;

            CurrentApplication::set($app);

            $payload = $task->getPayload();

            $this->dispatchEvent(
                app: $app,
                event: new JobReceivedEvent(
                    app: $app,
                    task: $task
                )
            );

            $taskId = $task->getId();

            $job = null;

            try {
                $job = $this->jobSerializer->unSerialize($payload);

                if (!$job->wait) {
                    $app->call($job->getCallback());
                } else {
                    /** @var JobsWaiter $waiter */
                    $waiter = $app->make(JobsWaiter::class);

                    $waiter->start($taskId);

                    $result = $app->call($job->getCallback());

                    $waiter->finish(
                        id: $taskId,
                        result: new JobResultDto(
                            result: $result
                        )
                    );

                    unset($result);
                }

                $task->ack();

                $this->dispatchEvent(
                    app: $app,
                    event: new JobHandledEvent(
                        app: $app,
                        task: $task
                    )
                );
            } catch (Throwable $exception) {
                if ($job?->wait) {
                    $app->make(JobsWaiter::class)->finish(
                        id: $taskId,
                        result: new JobResultDto(
                            exception: $exception
                        )
                    );
                }

                $task->nack($exception);

                $this->dispatchEvent(
                    app: $app,
                    event: new JobHandlingErrorEvent(
                        app: $app,
                        task: $task,
                        exception: $exception
                    )
                );
            } finally {
                $app->flush();

                unset($app);
            }
        }
    }
}
