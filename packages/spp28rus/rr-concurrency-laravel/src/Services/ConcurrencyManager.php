<?php

namespace RrConcurrency\Services;

use Closure;
use Illuminate\Support\Str;
use RrConcurrency\Exceptions\ConcurrencyException;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

readonly class ConcurrencyManager
{
    private Jobs $jobs;

    public function __construct(private RrJobsPayloadSerializer $payloadSerializer)
    {
        $rpcConnection = sprintf(
            'tcp://%s:%s',
            config('rr-concurrency.rpc.host'),
            config('rr-concurrency.rpc.port')
        );

        $this->jobs = new Jobs(
            RPC::create($rpcConnection)
        );
    }

    /**
     * @throws ConcurrencyException
     */
    public function go(Closure $callback): string
    {
        try {
            $id = $this->push(new ConcurrencyJob($callback))->getId();
        } catch (JobsException $exception) {
            throw new ConcurrencyException(previous: $exception);
        }

        return $id;
    }

    /**
     * @throws JobsException
     */
    private function push(ConcurrencyJob $job): QueuedTaskInterface
    {
        return $this->pushRaw(
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
    private function pushRaw($payload): QueuedTaskInterface
    {
        $queue = $this->makeQueue();

        $task = $this->makeTask($queue, $payload);

        return $queue->dispatch($task);
    }

    private function makePayload(ConcurrencyJob $job): string
    {
        return $this->payloadSerializer->serialize($job);
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
