<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use Illuminate\Support\Carbon;

readonly class RegisterTriggerParameters
{
    public function __construct(
        public WatcherObject $watcher,
        public WatcherEventPayloadInterface $payload,
        public Carbon $occurredAt
    ) {
    }
}
