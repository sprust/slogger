<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services\Locker;

use Illuminate\Support\Facades\Cache;
use Throwable;

class TraceLockerProxy
{
    private string $cacheKeyPrefix = 'trace:lock';

    public function __construct(private readonly string $traceId, private readonly object $target)
    {
    }

    /**
     * @throws Throwable
     */
    public function __call(string $method, mixed $parameters): mixed
    {
        $cacheKey = "$this->cacheKeyPrefix:$this->traceId";

        return Cache::lock($cacheKey, 15)->block(10, function () use ($method, $parameters) {
            return $this->target->{$method}(...$parameters);
        });
    }
}
