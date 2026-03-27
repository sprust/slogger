<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Domain\Actions\Mutations\FlushDynamicIndexesAction;
use Illuminate\Console\Command;

class FlushDynamicIndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-dynamic-indexes:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush trace dynamic indexes';

    /**
     * Execute the console command.
     */
    public function handle(FlushDynamicIndexesAction $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
