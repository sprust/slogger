<?php

namespace RrConcurrency\Services\Dto;

readonly class JobResultsDto
{
    public bool $hasFailed;

    /**
     * @param JobResultDto[] $results
     * @param JobResultDto[] $failed
     */
    public function __construct(
        public array $results,
        public array $failed
    ) {
        $this->hasFailed = (bool) $this->failed;
    }
}
