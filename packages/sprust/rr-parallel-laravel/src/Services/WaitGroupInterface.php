<?php

namespace RrParallel\Services;

use RrParallel\Exceptions\WaitTimeoutException;
use RrParallel\Services\Dto\JobResultsDto;

interface WaitGroupInterface
{
    public function current(): JobResultsDto;

    /**
     * @throws WaitTimeoutException
     */
    public function wait(int $waitSeconds): JobResultsDto;
}
