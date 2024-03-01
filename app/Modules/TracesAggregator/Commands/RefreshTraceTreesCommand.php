<?php

namespace App\Modules\TracesAggregator\Commands;

use App\Modules\TracesAggregator\Services\TraceTreesService;
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
    public function handle(TraceTreesService $traceTreesService): int
    {
        $traceTreesService->fresh();

        return self::SUCCESS;
    }
}
