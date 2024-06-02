<?php

namespace App\Console\Commands\Octane\Roadrunner;

use Illuminate\Console\Command;

class OctaneRoadrunnerReloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octane:roadrunner:reload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload the octane roadrunner workers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('octane:reload', [
            '--server' => 'roadrunner',
        ]);
    }
}
