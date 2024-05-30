<?php

namespace App\Console\Commands\Octane\Swoole;

use Illuminate\Console\Command;

class OctaneSwooleStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:swoole:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the octane swoole server';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('octane:swoole', [
            '--host'         => config('octane.servers.swoole.host'),
            '--port'         => config('octane.servers.swoole.port'),
            '--workers'      => config('octane.servers.swoole.workers'),
            '--task-workers' => config('octane.servers.swoole.task-workers'),
            '--max-requests' => config('octane.servers.swoole.max-requests'),
            '--watch'        => config('octane.servers.swoole.watch'),
            '--poll'         => config('octane.servers.swoole.poll'),
        ]);
    }
}
