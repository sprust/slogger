<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Contracts\Actions\StopTraceBufferHandlingActionInterface;
use Illuminate\Console\Command;

class StopTraceBufferHandlingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace-buffer:handle:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop of buffer handling';

    /**
     * Execute the console command.
     */
    public function handle(StopTraceBufferHandlingActionInterface $action): int
    {
        $action->handle($this->output);

        return self::SUCCESS;
    }
}
