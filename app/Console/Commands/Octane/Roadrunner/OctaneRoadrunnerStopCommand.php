<?php

namespace App\Console\Commands\Octane\Roadrunner;

use Illuminate\Console\Command;

class OctaneRoadrunnerStopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:roadrunner:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the octane roadrunner server';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('octane:stop', [
            '--server' => 'roadrunner',
        ]);
    }
}
