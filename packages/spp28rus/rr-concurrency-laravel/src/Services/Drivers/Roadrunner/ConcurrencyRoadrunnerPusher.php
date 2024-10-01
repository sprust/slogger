<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RrConcurrency\Exceptions\ConcurrencyJobsException;
use RrConcurrency\Services\ConcurrencyPusherInterface;
use RrConcurrency\Services\WaitGroupInterface;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

readonly class ConcurrencyRoadrunnerPusher implements ConcurrencyPusherInterface
{
    private Jobs $jobs;

    public function __construct(
        private Application $app,
        private RpcFactory $rpcFactory,
        private ConcurrencyJobSerializer $jobSerializer,
        private JobsWaiter $waiter,
        private HeadersResolver $headersResolver
    ) {
        $this->jobs = new Jobs(
            $this->rpcFactory->getRpc()
        );
    }

    public function push(Closure $callback): void
    {
        try {
            $this->pushJob(new ConcurrencyJob(callback: $callback, wait: false));
        } catch (JobsException $exception) {
            throw new ConcurrencyJobsException(previous: $exception);
        }
    }

    /**
     * @throws JobsException
     */
    public function pushMany(array $callbacks): void
    {
        $this->pushManyJobs(
            Arr::map(
                array: $callbacks,
                callback: fn(Closure $callback, mixed $key) => new ConcurrencyJob(
                    callback: $callback,
                    wait: true
                )
            )
        );
    }

    public function wait(array $callbacks): WaitGroupInterface
    {
        try {
            $tasks = $this->pushManyJobs(
                Arr::map(
                    array: $callbacks,
                    callback: fn(Closure $callback, mixed $key) => new ConcurrencyJob(
                        callback: $callback,
                        wait: true
                    )
                )
            );
        } catch (JobsException $exception) {
            throw new ConcurrencyJobsException(
                message: $exception->getMessage()
            );
        }

        $keys = array_keys($callbacks);

        $jobs = [];

        for ($index = 0; $index < count($tasks); $index++) {
            $jobs[$keys[$index]] = $tasks[$index];
        }

        return new WaitGroup($jobs, $this->waiter);
    }

    /**
     * @throws JobsException
     */
    private function pushJob(ConcurrencyJob $job): void
    {
        $this->pushRaw(
            payload: $this->makePayload($job),
        );
    }

    /**
     * @param ConcurrencyJob[] $jobs
     *
     * @return iterable<QueuedTaskInterface>
     *
     * @throws JobsException
     */
    private function pushManyJobs(array $jobs): iterable
    {
        $queue = $this->makeQueue();

        $tasks = [];

        foreach ($jobs as $job) {
            $tasks[] = $this->makeTask($queue, $this->makePayload($job));
        }

        return $queue->dispatchMany(...$tasks);
    }

    /**
     * @throws JobsException
     */
    private function pushRaw($payload): void
    {
        $queue = $this->makeQueue();

        $task = $this->makeTask($queue, $payload);

        $queue->dispatch($task);
    }

    private function makePayload(ConcurrencyJob $job): string
    {
        return $this->jobSerializer->serialize($job);
    }

    private function makeQueue(): QueueInterface
    {
        return $this->jobs->connect('concurrency');
    }

    private function makeTask(QueueInterface $queue, string $payload): PreparedTaskInterface
    {
        $task = $queue->create(
            name: Str::uuid()->toString(),
            payload: $payload
        );

        foreach ($this->headersResolver->get() as $name => $header) {
            $task = $task->withHeader(
                name: $name,
                value: is_callable($header) ? $this->app->call($header) : $header
            );
        }

        return $task;
    }
}
