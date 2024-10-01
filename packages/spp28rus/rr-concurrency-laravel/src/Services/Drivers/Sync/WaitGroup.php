<?php

namespace RrConcurrency\Services\Drivers\Sync;

use Closure;
use RrConcurrency\Services\Dto\JobResultDto;
use RrConcurrency\Services\Dto\JobResultsDto;
use RrConcurrency\Services\WaitGroupInterface;
use Throwable;

class WaitGroup implements WaitGroupInterface
{
    private ?JobResultsDto $result = null;

    /**
     * @param array<mixed, Closure> $callbacks
     */
    public function __construct(
        private readonly array $callbacks,
        private readonly ClosureHandler $closureHandler
    ) {
    }

    public function current(): JobResultsDto
    {
        return $this->handle();
    }

    public function wait(int $waitSeconds): JobResultsDto
    {
        return $this->handle();
    }

    private function handle(): JobResultsDto
    {
        if (!is_null($this->result)) {
            return $this->result;
        }

        $results = [];
        $failed  = [];

        foreach ($this->callbacks as $key => $callback) {
            try {
                $closureResult = $this->closureHandler->handleClosure(
                    callback: $callback
                );

                $result = new JobResultDto(
                    result: $closureResult
                );
            } catch (Throwable $exception) {
                $result = new JobResultDto(
                    exception: $exception
                );
            }

            $results[$key] = $result;

            if ($result->error) {
                $failed[$key] = $result;
            }
        }

        return $this->result = new JobResultsDto(
            finished: true,
            results: $results,
            failed: $failed
        );
    }
}
