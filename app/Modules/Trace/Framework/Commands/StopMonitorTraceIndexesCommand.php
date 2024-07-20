<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StopMonitorTraceIndexesActionInterface;
use Illuminate\Console\Command;

class StopMonitorTraceIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-indexes:monitor:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop monitor dynamic trace indexes';

    /**
     * Execute the console command.
     */
    public function handle(StopMonitorTraceIndexesActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
