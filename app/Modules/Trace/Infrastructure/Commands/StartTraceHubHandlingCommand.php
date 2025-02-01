<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Contracts\Actions\StartTraceHubHandlingActionInterface;
use Illuminate\Console\Command;

class StartTraceHubHandlingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-hub:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start trace hub handling';

    /**
     * Execute the console command.
     */
    public function handle(StartTraceHubHandlingActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
