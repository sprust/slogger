<?php

namespace RoadRunner\Servers\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Application;
use RoadRunner\Helpers\CurrentApplication;
use RoadRunner\Helpers\DispatchesEvents;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandledEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandlingErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadReceivedEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsServerErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerErrorEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerStartingEvent;
use RoadRunner\Servers\Jobs\Events\RrJobsWorkerStoppingEvent;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Worker;
use Throwable;

readonly class RrJobsServer
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
                new RrJobsServerErrorEvent($this->app, $exception)
            );
        }
    }

    private function onServe(): void
    {
        $this->dispatchEvent(
            $this->app,
            new RrJobsWorkerStartingEvent($this->app)
        );

        $consumer = new Consumer($this->worker);

        while (true) {
            try {
                $task = $consumer->waitTask();
            } catch (Throwable $exception) {
                $this->dispatchEvent(
                    $this->app,
                    new RrJobsWorkerErrorEvent($exception)
                );

                break;
            }

            $app = clone $this->app;

            CurrentApplication::set($app);

            $payload = $task->getPayload();

            $this->dispatchEvent(
                $app,
                new RrJobsPayloadReceivedEvent($app, $payload)
            );

            try {
                $result = $app[Dispatcher::class]->dispatchSync(
                    $this->payloadSerializer->unSerialize($payload)
                );

                $task->complete();

                $this->dispatchEvent(
                    $app,
                    new RrJobsPayloadHandledEvent($app, $payload, $result)
                );
            } catch (Throwable $exception) {
                $task->fail($exception);

                $this->dispatchEvent(
                    $app,
                    new RrJobsPayloadHandlingErrorEvent($app, $payload, $exception)
                );
            } finally {
                $app->flush();

                unset($app);

                CurrentApplication::set($this->app);
            }
        }

        $this->dispatchEvent(
            $this->app,
            new RrJobsWorkerStoppingEvent($this->app)
        );
    }
}
