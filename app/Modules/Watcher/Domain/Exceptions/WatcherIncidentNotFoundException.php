<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Exceptions;

use Exception;

class WatcherIncidentNotFoundException extends Exception
{
    public function __construct(int $incidentId)
    {
        parent::__construct("Watcher incident [$incidentId] not found");
    }
}
