<?php

namespace App\Modules\TraceCleaner\Domain\Events;

class ProcessAlreadyActiveEvent
{
    public function __construct(public int $settingId)
    {
    }
}
