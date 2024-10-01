<?php

namespace RrConcurrency\Services\Dto;

readonly class JobResultsDto
{
    public bool $hasFailed;

    /**
     * @param array<mixed, JobResultDto> $results
     * @param array<mixed, JobResultDto> $failed
     */
    public function __construct(
        public bool $finished,
        public array $results,
        public array $failed
    ) {
        $this->hasFailed = (bool) $this->failed;
    }
}
