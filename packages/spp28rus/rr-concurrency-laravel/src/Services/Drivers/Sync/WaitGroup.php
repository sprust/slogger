<?php

namespace RrConcurrency\Services\Drivers\Sync;

use Closure;
use RrConcurrency\Services\Dto\JobResultDto;
use RrConcurrency\Services\Dto\JobResultsDto;
use RrConcurrency\Services\WaitGroupInterface;
use Throwable;

readonly class WaitGroup implements WaitGroupInterface
{
    /**
     * @param Closure[] $callbacks
     */
    public function __construct(
        private array $callbacks,
        private ClosureHandler $closureHandler
    ) {
    }

    public function wait(int $waitSeconds): JobResultsDto
    {
        $results = [];
        $failed  = [];

        for ($index = 0; $index < count($this->callbacks); $index++) {
            try {
                $closureResult = $this->closureHandler->handleClosure(
                    $this->callbacks[$index]
                );

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
}
