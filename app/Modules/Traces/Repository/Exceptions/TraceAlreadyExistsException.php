<?php

namespace App\Modules\Traces\Repository\Exceptions;

use Exception;

class TraceAlreadyExistsException extends Exception
{
    /**
     * @param string[] $traceIds
     */
    public function __construct(array $traceIds)
    {
        $traceIdsString = implode(',', $traceIds);

        parent::__construct("Traces [$traceIdsString] already exists");
    }
}
