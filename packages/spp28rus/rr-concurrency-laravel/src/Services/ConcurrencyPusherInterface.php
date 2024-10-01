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
     * @param array<mixed, Closure> $callbacks
     *
     * @throws ConcurrencyJobsException
     */
    public function pushMany(array $callbacks): void;

    /**
     * @param array<mixed, Closure> $callbacks
     *
     * @throws ConcurrencyJobsException
     */
    public function wait(array $callbacks): WaitGroupInterface;
}
