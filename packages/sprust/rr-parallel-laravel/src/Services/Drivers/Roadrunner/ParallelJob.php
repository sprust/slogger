<?php

namespace RrParallel\Services\Drivers\Roadrunner;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

readonly class ParallelJob
{
    private string $payload;

    public function __construct(
        Closure $callback,
        public bool $wait
    ) {
        $this->payload = serialize(new SerializableClosure($callback));
    }

    public function getCallback(): Closure
    {
        return unserialize($this->payload)->getClosure();
    }
}
