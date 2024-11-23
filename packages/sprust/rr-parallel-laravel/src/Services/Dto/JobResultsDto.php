<?php

namespace RrParallel\Services\Dto;

/**
 * @template TResult
 */
readonly class JobResultsDto
{
    public bool $hasFailed;

    /**
     * @param array<mixed, JobResultDto<TResult>> $results
     * @param array<mixed, JobResultDto<TResult>> $failed
     */
    public function __construct(
        public bool $finished,
        public array $results,
        public array $failed
    ) {
        $this->hasFailed = (bool) $this->failed;
    }
}
