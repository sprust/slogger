<?php

namespace RrConcurrency\Services;

use Illuminate\Foundation\Application;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\DispatchesEvents;
use RrConcurrency\Events\JobsServerErrorEvent;
use RrConcurrency\Events\PayloadHandledEvent;
use RrConcurrency\Events\PayloadHandlingErrorEvent;
use RrConcurrency\Events\PayloadReceivedEvent;
use RrConcurrency\Events\WorkerErrorEvent;
use RrConcurrency\Events\WorkerStartingEvent;
use RrConcurrency\Events\WorkerStoppingEvent;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Worker;
use Throwable;

readonly class JobsServer
{
    use DispatchesEvents;

    private RrJobsPayloadSerializer $payloadSerializer;

    public function __construct(
        private Application $app,
        private Worker $worker
    ) {
        $this->payloadSerializer = $app->make(RrJobsPayloadSerializer::class);
    }

    public function serve(): void
    {
        try {
            $this->onServe();
        } catch (Throwable $exception) {
            $this->dispatchEvent(
                $this->app,
                new JobsServerErrorEvent($this->app, $exception)
            );
        }
    }

    private function onServe(): void
    {
        $this->dispatchEvent(
            $this->app,
            new WorkerStartingEvent($this->app)
        );

        $consumer = new Consumer($this->worker);

        while (true) {
            try {
                $task = $consumer->waitTask();
            } catch (Throwable $exception) {
                $this->dispatchEvent(
                    app: $this->app,
                    event: new WorkerErrorEvent($exception)
                );

                break;
            }

            $app = clone $this->app;

            CurrentApplication::set($app);

            $payload = $task->getPayload();

            $this->dispatchEvent(
                app: $app,
                event: new PayloadReceivedEvent($app, $payload)
            );

            try {
                $job = $this->payloadSerializer->unSerialize($payload);

                $result = $app->call($job->getCallback());

                if ($job->wait) {
                    $app->make(JobsCommunicator::class)->finish(
                        id: $task->getId(),
                        result: new JobResultDto(
                            serializedResult: serialize($result)
                        )
                    );
                }

                $task->ack();

                $this->dispatchEvent(
                    app: $app,
                    event: new PayloadHandledEvent(
                        app: $app,
                        payload: $payload,
                        result: $result
                    )
                );
            } catch (Throwable $exception) {
                $app->make(JobsCommunicator::class)->finish(
                    id: $task->getId(),
                    result: new JobResultDto(
                        exception: $exception
                    )
                );

                $task->nack($exception);

                $this->dispatchEvent(
                    app: $app,
                    event: new PayloadHandlingErrorEvent(
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

        $this->dispatchEvent(
            app: $this->app,
            event: new WorkerStoppingEvent($this->app)
        );
    }
}
