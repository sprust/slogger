<?php

namespace RrConcurrency\Services;

use Closure;
use RrConcurrency\Exceptions\ConcurrencyJobsException;
use RrConcurrency\Exceptions\ConcurrencyWaitTimeoutException;
use RrConcurrency\Services\Dto\JobResultsDto;

interface ConcurrencyServiceInterface
{
    /**
     * @throws ConcurrencyJobsException
     */
    public function go(Closure $callback): void;

    /**
     * @param Closure[] $callbacks
     *
     * @throws ConcurrencyJobsException
     * @throws ConcurrencyWaitTimeoutException
     */
    public function wait(array $callbacks, int $waitSeconds): JobResultsDto;
}
