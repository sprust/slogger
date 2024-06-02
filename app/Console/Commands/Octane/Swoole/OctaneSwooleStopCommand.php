<?php

namespace App\Console\Commands\Octane\Swoole;

use Illuminate\Console\Command;

class OctaneSwooleStopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:swoole:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the octane swoole server';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('octane:stop', [
            '--server' => 'swoole',
        ]);
    }
}
