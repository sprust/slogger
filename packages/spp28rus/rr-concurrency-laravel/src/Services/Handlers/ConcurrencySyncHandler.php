<?php

namespace RrConcurrency\Services\Handlers;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use RrConcurrency\Services\ConcurrencyJob;
use RrConcurrency\Services\Dto\JobResultDto;
use RrConcurrency\Services\Dto\JobResultsDto;
use RrConcurrency\Services\JobsPayloadSerializer;
use Throwable;

readonly class ConcurrencySyncHandler implements ConcurrencyHandlerInterface
{
    public function __construct(
        private Application $app,
        private JobsPayloadSerializer $payloadSerializer,
    ) {
    }

    public function handle(Closure $callback): void
    {
        $this->handleClosure($callback);
    }

    public function wait(array $callbacks, int $waitSeconds): JobResultsDto
    {
        $results = [];
        $failed  = [];

        for ($index = 0; $index < count($callbacks); $index++) {
            try {
                $closureResult = $this->handleClosure($callbacks[$index]);

                $result = new JobResultDto(
                    result: $closureResult
                );
            } catch (Throwable $exception) {
                $result = new JobResultDto(
                    exception: $exception
                );
            }

            $results[$index] = $result;

            if ($result->error) {
                $failed[$index] = $result;
            }
        }

        return new JobResultsDto(
            results: $results,
            failed: $failed
        );
    }

    private function handleClosure(Closure $callback): mixed
    {
        $payload = $this->payloadSerializer->serialize(
            new ConcurrencyJob(callback: $callback, wait: false)
        );

        $job = $this->payloadSerializer->unSerialize($payload);

        return $this->app->call($job->getCallback());
    }
}
