<?php

namespace App\Modules\Trace\Framework\Commands;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FreshTraceTreeActionInterface;
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
    public function handle(FreshTraceTreeActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
