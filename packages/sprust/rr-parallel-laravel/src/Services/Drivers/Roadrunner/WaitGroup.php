<?php

namespace RrParallel\Services\Drivers\Roadrunner;

use Psr\SimpleCache\InvalidArgumentException;
use RrParallel\Exceptions\WaitTimeoutException;
use RrParallel\Services\Dto\JobResultDto;
use RrParallel\Services\Dto\JobResultsDto;
use RrParallel\Services\WaitGroupInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

class WaitGroup implements WaitGroupInterface
{
    private ?JobResultsDto $results = null;

    /** @var array<mixed, JobResultDto> $jobsResult */
    private array $jobsResult = [];
    /** @var array<mixed, JobResultDto> $failedJobs */
    private array $failedJobs = [];

    private int $expectedCount;

    /**
     * @param array<mixed, QueuedTaskInterface> $jobs
     * @param JobsWaiter                        $waiter
     */
    public function __construct(
        private readonly array $jobs,
        private readonly JobsWaiter $waiter
    ) {
        $this->expectedCount = count($this->jobs);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function current(): JobResultsDto
    {
        if (!is_null($this->results)) {
            return $this->results;
        }

        $this->checkStatuses();

        return $this->makeResults(
            finished: $this->isFinished()
        );
    }

    /**
     * @throws WaitTimeoutException
     * @throws InvalidArgumentException
     */
    public function wait(int $waitSeconds): JobResultsDto
    {
        if (!is_null($this->results)) {
            return $this->results;
        }

        $start = time();

        while (true) {
            if ($this->isFinished()) {
                break;
            }

            if ((time() - $start) > $waitSeconds) {
                throw new WaitTimeoutException();
            }

            $this->checkStatuses();
        }

        return $this->makeResults(finished: true);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkStatuses(): void
    {
        foreach ($this->jobs as $key => $job) {
            if (array_key_exists($key, $this->jobsResult)) {
                continue;
            }

            $result = $this->waiter->result(
                id: $job->getId()
            );

            if (!$result) {
                continue;
            }

            $this->jobsResult[$key] = $result;

            if ($result->error) {
                $this->failedJobs[$key] = $result;
            }
        }
    }

    private function isFinished(): bool
    {
        return count($this->jobsResult) === $this->expectedCount;
    }

    private function makeResults(bool $finished): JobResultsDto
    {
        $jobsResult = [];
        $failedJobs = [];

        foreach (array_keys($this->jobs) as $key) {
            if (array_key_exists($key, $this->jobsResult)) {
                $jobsResult[$key] = $this->jobsResult[$key];
            }
            if (array_key_exists($key, $this->failedJobs)) {
                $failedJobs[$key] = $this->failedJobs[$key];
            }
        }

        $results = new JobResultsDto(
            finished: $finished,
            results: $jobsResult,
            failed: $failedJobs
        );

        if ($finished) {
            $this->results = $results;
        }

        return $results;
    }
}
