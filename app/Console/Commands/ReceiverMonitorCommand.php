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

    private bool $shouldQuit = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fileStatsPath = base_path('servers/receiver/storage/stats.json');

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () {
            $this->warn('Received SIGINT signal, stopping receiver monitor');

            $this->shouldQuit = true;
        });
        pcntl_signal(SIGTERM, function () {
            $this->warn('Received SIGINT signal, stopping receiver monitor');

            $this->shouldQuit = true;
        });

        while (!$this->shouldQuit) {
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

        return self::SUCCESS;
    }
}
