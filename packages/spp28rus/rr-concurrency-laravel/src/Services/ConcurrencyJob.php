<?php

namespace RrConcurrency\Services;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

readonly class ConcurrencyJob
{
    private string $payload;

    public function __construct(
        Closure $callback
    ) {
        $this->payload = serialize(new SerializableClosure($callback));
    }

    public function getCallback(): Closure
    {
        return unserialize($this->payload)->getClosure();
    }
}
