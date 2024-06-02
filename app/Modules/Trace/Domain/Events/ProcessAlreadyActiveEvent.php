<?php

namespace App\Modules\Trace\Domain\Events;

class ProcessAlreadyActiveEvent
{
    public function __construct(public int $settingId)
    {
    }
}
