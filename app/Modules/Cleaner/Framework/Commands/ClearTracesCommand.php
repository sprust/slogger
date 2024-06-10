<?php

namespace App\Modules\Cleaner\Framework\Commands;

use App\Modules\Cleaner\Domain\Actions\Interfaces\ClearTracesActionInterface;
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
    public function handle(ClearTracesActionInterface $clearTracesAction): int
    {
        $clearTracesAction->handle();

        return self::SUCCESS;
    }
}
