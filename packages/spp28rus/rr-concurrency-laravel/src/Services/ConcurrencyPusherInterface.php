<?php

namespace RrConcurrency\Services;

use Closure;
use RrConcurrency\Exceptions\ConcurrencyJobsException;

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
     */
    public function wait(array $callbacks): WaitGroupInterface;
}
