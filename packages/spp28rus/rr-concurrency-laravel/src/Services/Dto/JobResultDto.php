<?php

namespace RrConcurrency\Services\Dto;

use Throwable;

readonly class JobResultDto
{
    public ?JobResultErrorDto $error;
    public ?string $serializedResult;

    public function __construct(
        ?Throwable $exception = null,
        ?string $serializedResult = null
    ) {
        $this->error            = $exception ? new JobResultErrorDto($exception) : null;
        $this->serializedResult = $serializedResult;
    }
}
