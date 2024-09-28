<?php

namespace RrConcurrency\Services\Handlers;

use Closure;
use RrConcurrency\Exceptions\ConcurrencyJobsException;
use RrConcurrency\Exceptions\ConcurrencyWaitTimeoutException;
use RrConcurrency\Services\Dto\JobResultsDto;

interface ConcurrencyPusherInterface
{
    /**
     * @throws ConcurrencyJobsException
     */
    public function push(Closure $callback): void;

    /**
     * @param Closure[] $callbacks
     *
     * @throws ConcurrencyJobsException
     * @throws ConcurrencyWaitTimeoutException
     */
    public function wait(array $callbacks, int $waitSeconds): JobResultsDto;
}
