<?php

namespace RoadRunner\Servers\Jobs;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;

class RrQueueConnection extends Queue implements QueueContract
{
    private Jobs $jobs;

    public function __construct(private readonly RrJobsPayloadSerializer $payloadSerializer)
    {
        $rpcConnection = sprintf(
            'tcp://%s:%s',
            config('roadrunner.rpc.host'),
            config('roadrunner.rpc.port')
        );

        $this->jobs = new Jobs(
            RPC::create($rpcConnection)
        );
    }

    public function size($queue = null)
    {
        if (!$queue) {
            return $this->jobs->count();
        }

        $count = 0;

        foreach ($this->jobs->getIterator() as $item) {
            if ($item->getName() === $queue) {
                ++$count;
            }
        }

        return $count;
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw(
            payload: $this->makePayload($job),
            queue: $queue,
            options: ['name' => $job::class]
        );
    }

    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $queue = $this->makeQueue($queue);

        $task = $this->makeTask($queue, $options['name'], $payload);

        return $queue->dispatch($task);
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $queue = $this->makeQueue($queue);

        $task = $this->makeTask($queue, $job::class, $this->makePayload($job));

        $task->withDelay($delay);

        return $queue->dispatch($task);
    }

    public function laterOn($queue, $delay, $job, $data = '')
    {
        $this->later($delay, $job, $data, $queue);
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->makeQueue($queue);

        $tasks = [];

        foreach ($jobs as $job) {
            $tasks[] = $this->makeTask($queue, $job::class, $this->makePayload($job));
        }

        $queue->dispatchMany(...$tasks);
    }

    public function pop($queue = null)
    {
        throw new \RuntimeException('Not supported');
    }

    public function getConnectionName()
    {
        return 'roadrunner';
    }

    public function setConnectionName($name)
    {
        return $this;
    }

    private function makePayload(object $job): string
    {
        return $this->payloadSerializer->serialize($job);
    }

    private function makeQueue(mixed $queue): QueueInterface
    {
        return $this->jobs->connect($queue ?: 'default');
    }

    private function makeTask(QueueInterface $queue, $name, string $payload): PreparedTaskInterface
    {
        return $queue->create(
            name: $name ?? 'noname',
            payload: $payload
        );
    }
}
