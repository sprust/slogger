<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use Illuminate\Support\Carbon;

readonly class WatcherCollectionStart
{
    public const int RECEIVER_RELOAD_SECONDS = 30;

    public function afterNextReload(Carbon $now): Carbon
    {
        return $now->clone()->addSeconds(self::RECEIVER_RELOAD_SECONDS);
    }
}
