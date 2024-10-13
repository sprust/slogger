<?php

namespace App\Modules\Dashboard\Infrastructure\Http\Jobs;

use App\Modules\Dashboard\Contracts\Actions\CacheServiceStatActionInterface;
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
    public function handle(CacheServiceStatActionInterface $action): void
    {
        $action->handle();
    }
}
