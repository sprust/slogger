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
    protected $signature = 'traces-aggregator:fresh-trees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh trace trees';

    /**
     * Execute the console command.
     */
    public function handle(TraceTreeService $traceAggregatorTreesService): int
    {
        $traceAggregatorTreesService->fresh();

        return self::SUCCESS;
    }
}
