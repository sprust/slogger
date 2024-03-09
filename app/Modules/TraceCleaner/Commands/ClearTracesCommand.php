<?php

namespace App\Modules\TraceCleaner\Commands;

use App\Modules\TraceCleaner\Services\CleaningService;
use Illuminate\Console\Command;

class ClearTracesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-clearing:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleaning up traces using cleanup settings';

    /**
     * Execute the console command.
     */
    public function handle(CleaningService $service): int
    {
        $service->clear();

        return self::SUCCESS;
    }
}
