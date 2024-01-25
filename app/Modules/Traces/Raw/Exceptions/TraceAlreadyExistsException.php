<?php

namespace App\Modules\Traces\Raw\Exceptions;

use Exception;

class TraceAlreadyExistsException extends Exception
{
    /**
     * @param string[] $trackIds
     */
    public function __construct(array $trackIds)
    {
        $trackIdsString = implode(',', $trackIds);

        parent::__construct("Raw logs with track ids [$trackIdsString] already exists");
    }
}
