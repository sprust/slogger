<?php

namespace RrParallel\Services\Dto;

use Throwable;

readonly class JobResultErrorDto
{
    public string $exceptionClass;
    public string $message;
    public string $traceAsString;
    public ?JobResultErrorDto $previous;

    public function __construct(Throwable $exception)
    {
        $previous = $exception->getPrevious();

        $this->exceptionClass = $exception::class;
        $this->message        = $exception->getMessage();
        $this->traceAsString  = $exception->getTraceAsString();
        $this->previous       = $previous ? new JobResultErrorDto($exception) : null;
    }
}
