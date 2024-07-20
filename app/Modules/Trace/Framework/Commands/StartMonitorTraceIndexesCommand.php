<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StartMonitorTraceIndexesActionInterface;
use Illuminate\Console\Command;

class StartMonitorTraceIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-indexes:monitor:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start monitor dynamic trace indexes';

    /**
     * Execute the console command.
     */
    public function handle(StartMonitorTraceIndexesActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
