<?php

namespace App\Modules\Cleaner\Framework\Jobs;

use App\Modules\Cleaner\Domain\Actions\Interfaces\ClearTracesActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ClearTracesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onConnection(config('cleaner.queue.connection'))
            ->onQueue(config('cleaner.queue.name'));
    }

    /**
     * Execute the job.
     */
    public function handle(ClearTracesActionInterface $clearTracesAction): void
    {
        $clearTracesAction->handle();
    }
}
