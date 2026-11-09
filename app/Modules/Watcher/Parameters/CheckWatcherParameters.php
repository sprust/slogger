<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;

readonly class CheckWatcherParameters
{
    public function __construct(
        public WatcherObject $watcher,
        public WatcherCheckContextObject $context
    ) {
    }
}
