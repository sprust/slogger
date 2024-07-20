<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\MonitorTraceIndexesActionInterface;
use Illuminate\Console\Command;

class MonitorTraceIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-indexes:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor dynamic trace indexes';

    /**
     * Execute the console command.
     */
    public function handle(MonitorTraceIndexesActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
