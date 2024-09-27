<?php

namespace RrConcurrency\Services\Dto;

use Throwable;

readonly class JobResultDto
{
    public ?JobResultErrorDto $error;
    public mixed $result;

    public function __construct(
        ?Throwable $exception = null,
        mixed $result = null
    ) {
        $this->error  = $exception ? new JobResultErrorDto($exception) : null;
        $this->result = $result;
    }
}
