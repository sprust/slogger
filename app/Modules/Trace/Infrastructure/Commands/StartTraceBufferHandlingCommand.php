<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Contracts\Actions\StartTraceBufferHandlingActionInterface;
use Illuminate\Console\Command;

class StartTraceBufferHandlingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-buffer:handle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start of buffer handling';

    /**
     * Execute the console command.
     */
    public function handle(StartTraceBufferHandlingActionInterface $action): int
    {
        $action->handle($this->output);

        return self::SUCCESS;
    }
}
