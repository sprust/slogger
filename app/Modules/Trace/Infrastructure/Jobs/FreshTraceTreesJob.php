<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Contracts\Actions\Mutations\FreshTraceTreesActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FreshTraceTreesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public function handle(FreshTraceTreesActionInterface $freshTraceTreesAction): void
    {
        $freshTraceTreesAction->handle();
    }
}
