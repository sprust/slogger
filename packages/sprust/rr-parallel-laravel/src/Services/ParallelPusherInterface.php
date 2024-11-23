<?php

namespace RrParallel\Services;

use Closure;
use RrParallel\Exceptions\ParallelJobsException;

interface ParallelPusherInterface
{
    /**
     * @throws ParallelJobsException
     */
    public function push(Closure $callback): void;

    /**
     * @param array<mixed, Closure> $callbacks
     *
     * @throws ParallelJobsException
     */
    public function pushMany(array $callbacks): void;

    /**
     * @param array<mixed, Closure> $callbacks
     *
     * @throws ParallelJobsException
     */
    public function wait(array $callbacks): WaitGroupInterface;
}
