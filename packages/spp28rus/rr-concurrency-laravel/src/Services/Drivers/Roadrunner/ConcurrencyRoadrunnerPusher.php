<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use Closure;
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
        private RpcFactory $rpcFactory,
        private ConcurrencyJobSerializer $jobSerializer,
        private JobsWaiter $waiter,
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

    public function wait(array $callbacks): WaitGroupInterface
    {
        try {
            $jobs = $this->pushMany(
                array_map(
                    fn(Closure $callback) => new ConcurrencyJob(
                        callback: $callback,
                        wait: true
                    ),
                    $callbacks
                )
            );
        } catch (JobsException $exception) {
            throw new ConcurrencyJobsException(
                message: $exception->getMessage()
            );
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
    private function pushMany(array $jobs): iterable
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
        return $queue->create(
            name: Str::uuid()->toString(),
            payload: $payload
        );
    }
}
