<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Exceptions;

class WatcherNotFoundException extends AbstractWatcherException
{
    public function __construct(int $watcherId)
    {
        parent::__construct("Watcher [$watcherId] not found");
    }
}
