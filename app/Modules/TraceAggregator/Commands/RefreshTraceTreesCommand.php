<?php

namespace App\Modules\TraceAggregator\Commands;

use App\Modules\TraceAggregator\Services\TraceTreeService;
use Illuminate\Console\Command;

class RefreshTraceTreesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-aggregator:fresh-trees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh trace trees';

    /**
     * Execute the console command.
     */
    public function handle(TraceTreeService $traceTreeService): int
    {
        $traceTreeService->fresh();

        return self::SUCCESS;
    }
}
