<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Jobs;

use App\Modules\Dashboard\Domain\Actions\RefreshDatabaseStatCacheAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RefreshDatabaseStatCacheJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public function handle(RefreshDatabaseStatCacheAction $action): void
    {
        $action->handle();
    }
}
