<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReceiverMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receiver:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor receiver';

    /**
     * Execute the console command.
     */
    public function handle(): never
    {
        $fileStatsPath = base_path('servers/receiver/storage/stats.json');

        while (true) {
            system('clear');

            $this->info('Receiver monitor:');
            $this->info(now()->toDateTimeString());

            if (!file_exists($fileStatsPath)) {
                $this->warn('Receiver stats file not found');
            } else {
                $content = file_get_contents($fileStatsPath);

                $this->warn($content);
            }

            sleep(1);
        }
    }
}
