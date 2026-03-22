<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Domain\Actions\Mutations\FreshTraceTreesAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FreshTraceTreesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public function handle(FreshTraceTreesAction $freshTraceTreesAction): void
    {
        $freshTraceTreesAction->handle();
    }
}
