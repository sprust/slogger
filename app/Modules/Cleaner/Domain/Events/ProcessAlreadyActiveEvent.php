<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Events;

class ProcessAlreadyActiveEvent
{
    public function __construct(public int $settingId)
    {
    }
}
