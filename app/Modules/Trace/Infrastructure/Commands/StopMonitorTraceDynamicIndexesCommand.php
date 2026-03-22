<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Domain\Actions\Mutations\StopMonitorTraceDynamicIndexesAction;
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
    public function handle(StopMonitorTraceDynamicIndexesAction $action): int
    {
        $action->handle();

        $this->components->info('The stop signal of trace dynamic indexes monitor sent');

        return self::SUCCESS;
    }
}
