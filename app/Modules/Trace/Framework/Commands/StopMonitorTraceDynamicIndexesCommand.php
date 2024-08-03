<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\StopMonitorTraceDynamicIndexesActionInterface;
use Illuminate\Console\Command;

class StopMonitorTraceDynamicIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-dynamic-indexes:monitor:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop monitor dynamic trace indexes';

    /**
     * Execute the console command.
     */
    public function handle(StopMonitorTraceDynamicIndexesActionInterface $action): int
    {
        $action->handle();

        $this->components->info('The stop signal of trace dynamic indexes monitor sent');

        return self::SUCCESS;
    }
}
