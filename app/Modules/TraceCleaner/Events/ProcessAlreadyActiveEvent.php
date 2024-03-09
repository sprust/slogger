<?php

namespace App\Modules\TraceCleaner\Events;

class ProcessAlreadyActiveEvent
{
    public function __construct(public int $settingId)
    {
    }
}
