<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use Illuminate\Foundation\Application;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\DispatchesEvents;
use RrConcurrency\Events\JobHandledEvent;
use RrConcurrency\Events\JobHandlingErrorEvent;
use RrConcurrency\Events\JobReceivedEvent;
use RrConcurrency\Events\JobWaitingErrorEvent;
use RrConcurrency\Events\WorkerServeErrorEvent;
use RrConcurrency\Events\WorkerStartingEvent;
use RrConcurrency\Events\WorkerStoppedEvent;
use RrConcurrency\Services\Dto\JobResultDto;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Worker;
use Throwable;

readonly class JobsServer
{
    use DispatchesEvents;

    private ConcurrencyJobSerializer $jobSerializer;

    public function __construct(
        private Application $app,
        private Worker $worker
    ) {
        $this->jobSerializer = $app->make(ConcurrencyJobSerializer::class);
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
                event: new JobReceivedEvent($app, $payload)
            );

            try {
                $job = $this->jobSerializer->unSerialize($payload);

                $result = $app->call($job->getCallback());

                if ($job->wait) {
                    $app->make(JobsWaiter::class)->finish(
                        id: $task->getId(),
                        result: new JobResultDto(
                            result: $result
                        )
                    );
                }

                $task->ack();

                $this->dispatchEvent(
                    app: $app,
                    event: new JobHandledEvent(
                        app: $app,
                        payload: $payload,
                        result: $result
                    )
                );
            } catch (Throwable $exception) {
                $app->make(JobsWaiter::class)->finish(
                    id: $task->getId(),
                    result: new JobResultDto(
                        exception: $exception
                    )
                );

                $task->nack($exception);

                $this->dispatchEvent(
                    app: $app,
                    event: new JobHandlingErrorEvent(
                        app: $app,
                        payload: $payload,
                        exception: $exception
                    )
                );
            } finally {
                $app->flush();

                unset($app);

                CurrentApplication::set($this->app);
            }
        }
    }
}
