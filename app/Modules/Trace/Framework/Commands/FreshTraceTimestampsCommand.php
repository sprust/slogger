<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FreshTraceTimestampsActionInterface;
use Illuminate\Console\Command;

class FreshTraceTimestampsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-collector:fresh-timestamps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fresh trace timestamps';

    /**
     * Execute the console command.
     */
    public function handle(FreshTraceTimestampsActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
