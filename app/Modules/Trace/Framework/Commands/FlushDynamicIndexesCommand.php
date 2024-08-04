<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FlushDynamicIndexesActionInterface;
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
    public function handle(FlushDynamicIndexesActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
