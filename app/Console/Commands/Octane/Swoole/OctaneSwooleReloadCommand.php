<?php

namespace App\Console\Commands\Octane\Swoole;

use Illuminate\Console\Command;

class OctaneSwooleReloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:swoole:reload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload the octane swoole workers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('octane:reload', [
            '--server' => 'swoole',
        ]);
    }
}
