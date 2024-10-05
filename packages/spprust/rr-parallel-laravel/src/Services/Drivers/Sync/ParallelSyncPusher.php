<?php

namespace RrParallel\Services\Drivers\Sync;

use Closure;
use RrParallel\Services\ParallelPusherInterface;
use RrParallel\Services\WaitGroupInterface;

readonly class ParallelSyncPusher implements ParallelPusherInterface
{
    public function __construct(private ClosureHandler $closureHandler)
    {
    }

    public function push(Closure $callback): void
    {
        $this->closureHandler->handleClosure($callback);
    }

    public function pushMany(array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $this->closureHandler->handleClosure($callback);
        }
    }

    public function wait(array $callbacks): WaitGroupInterface
    {
        return new WaitGroup($callbacks, $this->closureHandler);
    }
}
