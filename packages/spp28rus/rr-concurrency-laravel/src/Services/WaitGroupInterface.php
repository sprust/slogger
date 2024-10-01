<?php

namespace RrConcurrency\Services;

use RrConcurrency\Exceptions\ConcurrencyWaitTimeoutException;
use RrConcurrency\Services\Dto\JobResultsDto;

interface WaitGroupInterface
{
    public function current(): JobResultsDto;

    /**
     * @throws ConcurrencyWaitTimeoutException
     */
    public function wait(int $waitSeconds): JobResultsDto;
}
