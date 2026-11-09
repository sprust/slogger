<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Jobs;

use App\Modules\Cleaner\Domain\UseCases\ClearTracesUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearTracesJob implements ShouldQueue
{
    use InteractsWithQueue;

    use Queueable;

    public int $tries   = 3;

    public int $backoff = 2;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this
            ->onConnection(
                config(
                    key: 'cleaner.queue.connection',
                )
            )
            ->onQueue(
                config(
                    key: 'cleaner.queue.name',
                )
            );
    }

    /**
     * Execute the job.
     */
    public function handle(ClearTracesUseCase $clearTracesUseCase): void
    {
        $clearTracesUseCase->handle();
    }
}
