<?php

namespace App\Modules\ProjectLogs\ProjectLogsRaw\Exceptions;

use Exception;

class RawLogsAlreadyExistsException extends Exception
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
