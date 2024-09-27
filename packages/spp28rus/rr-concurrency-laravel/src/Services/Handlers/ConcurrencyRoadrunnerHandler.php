<?php

namespace RrConcurrency\Services\Handlers;

use Closure;
use Illuminate\Support\Str;
use RrConcurrency\Exceptions\ConcurrencyJobsException;
use RrConcurrency\Exceptions\ConcurrencyWaitTimeoutException;
use RrConcurrency\Services\ConcurrencyJob;
use RrConcurrency\Services\Dto\JobResultDto;
use RrConcurrency\Services\Dto\JobResultsDto;
use RrConcurrency\Services\JobsPayloadSerializer;
use RrConcurrency\Services\JobsWaiter;
use RrConcurrency\Services\Roadrunner\RpcFactory;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

readonly class ConcurrencyRoadrunnerHandler implements ConcurrencyHandlerInterface
{
    private Jobs $jobs;

    public function __construct(
        private RpcFactory $rpcFactory,
        private JobsPayloadSerializer $payloadSerializer,
        private JobsWaiter $waiter,
    ) {
        $this->jobs = new Jobs(
            $this->rpcFactory->getRpc()
        );
    }

    public function handle(Closure $callback): void
    {
        try {
            $this->push(new ConcurrencyJob(callback: $callback, wait: false));
        } catch (JobsException $exception) {
            throw new ConcurrencyJobsException(previous: $exception);
        }
    }

    public function wait(array $callbacks, int $waitSeconds): JobResultsDto
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

        /** @var JobResultDto[] $failedJobs */
        $failedJobs = [];

        $expectedCount = count($callbacks);

        /** @var JobResultDto[] $results */
        $results = [];

        $start = time();

        while (true) {
            if (count($results) === $expectedCount) {
                break;
            }

            if ((time() - $start) > $waitSeconds) {
                throw new ConcurrencyWaitTimeoutException();
            }

            for ($index = 0; $index < $expectedCount; $index++) {
                $job = $jobs[$index];

                if (array_key_exists($index, $results)) {
                    continue;
                }

                $result = $this->waiter->result(
                    id: $job->getId()
                );

                if (!$result) {
                    continue;
                }

                $results[$index] = $result;

                if ($result->error) {
                    $failedJobs[$index] = $result;
                }
            }

            foreach ($jobs as $job) {
                $job->getId();
            }
        }

        ksort($results);
        ksort($failedJobs);

        return new JobResultsDto(
            results: $results,
            failed: $failedJobs
        );
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
