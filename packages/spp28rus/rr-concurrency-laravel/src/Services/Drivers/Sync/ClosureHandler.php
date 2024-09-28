<?php

namespace RrConcurrency\Services\Drivers\Sync;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use RrConcurrency\Services\Drivers\Roadrunner\ConcurrencyJob;
use RrConcurrency\Services\Drivers\Roadrunner\ConcurrencyJobSerializer;

readonly class ClosureHandler
{
    public function __construct(
        private Application $app,
        private ConcurrencyJobSerializer $jobSerializer,
    ) {
    }

    public function handleClosure(Closure $callback): mixed
    {
        $payload = $this->jobSerializer->serialize(
            new ConcurrencyJob(callback: $callback, wait: false)
        );

        $job = $this->jobSerializer->unSerialize($payload);

        return $this->app->call($job->getCallback());
    }
}
