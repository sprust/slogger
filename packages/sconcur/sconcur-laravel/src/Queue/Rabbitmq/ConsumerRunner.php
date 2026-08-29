<?php

declare(strict_types=1);

namespace SConcur\Laravel\Queue\Rabbitmq;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use SConcur\Features\Amqp\Consumer\QueueConsumer;
use SConcur\Features\Amqp\Delivery;
use SConcur\Laravel\Foundation\AsyncApplication;

/**
 * Runs a queue-consumer pool in the current process, the way HttpServerRunner runs an
 * HTTP server: the master spawns this as a group's worker, one process per slot, and
 * each process reads all of its queues at once with a coroutine per delivery.
 *
 * Illuminate\Queue\Worker::daemon() is deliberately not used. It is a strictly
 * sequential loop — getNextJob() -> pop() -> runJob() -> sleep() — one job at a time,
 * and its sleep() blocks the process, so a coroutine runtime would buy nothing.
 * process() is the half worth having: it raises the job events, honours maxTries and
 * backoff, and writes failed_jobs.
 */
readonly class ConsumerRunner
{
    public function __construct(
        private QueueConsumer $consumer,
        private string $connectionName,
        private WorkerOptions $options,
    ) {
    }

    public function run(Application $app): int
    {
        // Turn on coroutine-scoped resolution for the lifetime of this worker: handlers
        // run concurrently, so auth, session, cookie, the config overlay, the dispatcher
        // and the translator have to be per-coroutine rather than per-process.
        if ($app instanceof AsyncApplication) {
            $app->enableAsyncMode();
        }

        /** @var QueueManager $manager */
        $manager = $app->make(QueueManager::class);

        /** @var Queue $queue */
        $queue = $manager->connection($this->connectionName);

        // The binding, not the class: Worker's constructor takes primitives Laravel
        // fills in itself (QueueServiceProvider::registerWorker), so autowiring it
        // would fail on an unresolvable dependency.
        /** @var Worker $worker */
        $worker = $app->make('queue.worker');

        $this->logFailedJobs($app);

        return $this->consumer->consume(
            connection: $queue->getConnection(),
            handler: function (Delivery $delivery) use ($app, $queue, $worker): void {
                $worker->process(
                    $this->connectionName,
                    new Job(
                        container: $app,
                        rabbitmq: $queue,
                        delivery: $delivery,
                        connectionName: $this->connectionName,
                        // A delivery does not carry the queue it came from, and the
                        // routing key is what stands in for it: publishing into the
                        // default exchange routes by queue name, which is what this
                        // driver does and what laravel-queue-rabbitmq does by default.
                        // The two agree, and a release() therefore goes back where the
                        // job came from.
                        queue: $delivery->routingKey,
                    ),
                    $this->options,
                );
            },
        );
    }

    /**
     * Write failed jobs to the failed_jobs store.
     *
     * Worker::process() does not do this: it marks the job failed and raises JobFailed,
     * and the write itself lives in the queue:work command, which this pool replaces.
     * Without repeating it here a job that exhausted its attempts would vanish with no
     * record at all.
     */
    protected function logFailedJobs(Application $app): void
    {
        /** @var Dispatcher $events */
        $events = $app->make('events');

        $events->listen(JobFailed::class, static function (JobFailed $event) use ($app): void {
            /** @var FailedJobProviderInterface $failer */
            $failer = $app->make('queue.failer');

            $failer->log(
                $event->connectionName,
                $event->job->getQueue(),
                $event->job->getRawBody(),
                $event->exception,
            );
        });
    }
}
