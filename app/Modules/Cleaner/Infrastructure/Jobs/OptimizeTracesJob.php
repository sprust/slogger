<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Jobs;

use App\Modules\Cleaner\Domain\Actions\OptimizeTracesAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OptimizeTracesJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    // Once: the next hour takes whatever this one did not finish.
    public int $tries = 1;

    public function __construct()
    {
        $this->onConnection(config('cleaner.queue.connection'))
            ->onQueue(config('cleaner.queue.name'));
    }

    public function handle(OptimizeTracesAction $optimizeTracesAction): void
    {
        $optimizeTracesAction->handle();
    }
}
