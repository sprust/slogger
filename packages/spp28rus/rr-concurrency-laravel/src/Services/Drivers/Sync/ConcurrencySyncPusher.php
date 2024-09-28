<?php

namespace RrConcurrency\Services\Drivers\Sync;

use Closure;
use RrConcurrency\Services\ConcurrencyPusherInterface;
use RrConcurrency\Services\WaitGroupInterface;

readonly class ConcurrencySyncPusher implements ConcurrencyPusherInterface
{
    public function __construct(private ClosureHandler $closureHandler)
    {
    }

    public function push(Closure $callback): void
    {
        $this->closureHandler->handleClosure($callback);
    }

    public function wait(array $callbacks): WaitGroupInterface
    {
        return new WaitGroup($callbacks, $this->closureHandler);
    }
}
