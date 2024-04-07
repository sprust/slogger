<?php

namespace App\Modules\TraceCleaner\Framework\Commands;

use App\Modules\TraceCleaner\Domain\Actions\ClearAction;
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
    public function handle(ClearAction $clearAction): int
    {
        $clearAction->handle();

        return self::SUCCESS;
    }
}
