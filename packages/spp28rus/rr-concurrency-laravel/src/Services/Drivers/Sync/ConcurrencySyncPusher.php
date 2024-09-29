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
