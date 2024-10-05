<?php

namespace RrParallel\Services\Drivers\Sync;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use RrParallel\Services\Drivers\Roadrunner\ParallelJob;
use RrParallel\Services\Drivers\Roadrunner\ParallelJobSerializer;

readonly class ClosureHandler
{
    public function __construct(
        private Application $app,
        private ParallelJobSerializer $jobSerializer,
    ) {
    }

    public function handleClosure(Closure $callback): mixed
    {
        $payload = $this->jobSerializer->serialize(
            new ParallelJob(callback: $callback, wait: false)
        );

        $job = $this->jobSerializer->unSerialize($payload);

        return $this->app->call($job->getCallback());
    }
}
