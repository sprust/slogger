<?php

namespace App\Modules\ProjectLogs\RawLogs\Exceptions;

use Exception;

class ProjectRawLogsAlreadyExistsException extends Exception
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
