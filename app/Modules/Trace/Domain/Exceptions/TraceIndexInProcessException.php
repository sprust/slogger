<?php

namespace App\Modules\Trace\Domain\Exceptions;

use App\Modules\Trace\Domain\Entities\Objects\TraceIndexObject;
use Exception;

class TraceIndexInProcessException extends Exception
{
    public function __construct(private readonly TraceIndexObject $traceIndex)
    {
    }

    public function getTraceIndex(): TraceIndexObject
    {
        return $this->traceIndex;
    }
}
