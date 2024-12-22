<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services\Locker;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Throwable;

readonly class TraceLocker
{
    public function __construct(private Application $app)
    {
    }

    /**
     * @template TClass
     *
     * @param class-string<TClass> $class
     *
     * @return TClass
     */
    public function resolve(string $traceId, string $class)
    {
        try {
            $result = new TraceLockerProxy(
                traceId: $traceId,
                target: $this->app->make($class)
            );
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }

        return $result;
    }
}
