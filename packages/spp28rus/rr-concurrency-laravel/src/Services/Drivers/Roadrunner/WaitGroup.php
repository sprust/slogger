<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use RrConcurrency\Exceptions\ConcurrencyWaitTimeoutException;
use RrConcurrency\Services\Dto\JobResultDto;
use RrConcurrency\Services\Dto\JobResultsDto;
use RrConcurrency\Services\WaitGroupInterface;

readonly class WaitGroup implements WaitGroupInterface
{
    public function __construct(private array $jobs, private JobsWaiter $waiter)
    {
    }

    /**
     * @throws ConcurrencyWaitTimeoutException
     */
    public function wait(int $waitSeconds): JobResultsDto
    {
        /** @var JobResultDto[] $failedJobs */
        $failedJobs = [];

        $expectedCount = count($this->jobs);

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
                $job = $this->jobs[$index];

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
        }

        ksort($results);
        ksort($failedJobs);

        return new JobResultsDto(
            results: $results,
            failed: $failedJobs
        );
    }
}
