<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Domain\Actions\Mutations\StartMonitorTraceDynamicIndexesAction;
use Illuminate\Console\Command;

class StartMonitorTraceDynamicIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-dynamic-indexes:monitor:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start monitor dynamic trace indexes';

    /**
     * Execute the console command.
     */
    public function handle(StartMonitorTraceDynamicIndexesAction $action): int
    {
        $this->components->info('The monitor dynamic trace indexes is starting');

        $action->handle();

        return self::SUCCESS;
    }
}
