<?php

namespace RrParallel\Services\Dto;

use Throwable;

/**
 * @template TResult
 */
readonly class JobResultDto
{
    public ?JobResultErrorDto $error;
    /**
     * @var TResult
     */
    public mixed $result;

    public function __construct(
        ?Throwable $exception = null,
        mixed $result = null
    ) {
        $this->error  = $exception ? new JobResultErrorDto($exception) : null;
        $this->result = $result;
    }
}
