<?php

namespace App\Modules\Dashboard\Framework\Http\Jobs;

use App\Modules\Dashboard\Domain\Actions\CacheServiceStatAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CacheServiceStatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     */
    public function handle(CacheServiceStatAction $action): void
    {
        $action->handle();
    }
}
