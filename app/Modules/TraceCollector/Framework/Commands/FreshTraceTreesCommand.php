<?php

namespace App\Modules\TraceCollector\Framework\Commands;

use App\Modules\TraceCollector\Domain\Actions\FreshTraceTreeAction;
use Illuminate\Console\Command;

class FreshTraceTreesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-collector:fresh-trees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh trace trees';

    /**
     * Execute the console command.
     */
    public function handle(FreshTraceTreeAction $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
