<?php

namespace App\Modules\Traces\Raw\Exceptions;

use Exception;

class TraceAlreadyExistsException extends Exception
{
    /**
     * @param string[] $traceIds
     */
    public function __construct(array $traceIds)
    {
        $traceIdsString = implode(',', $traceIds);

        parent::__construct("Raw logs with trace ids [$traceIdsString] already exists");
    }
}
