<?php

namespace App\Console\Commands\Cron;

use Illuminate\Support\Facades\Artisan;

class CronStartCommand extends BaseCronCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the cron';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Cron start');

        $sessionKey = $this->setSessionKey();

        $previousMinute = $this->getCurrentMinute();

        while (true) {
            sleep(5);

            if (!$this->isSessionKeyActive($sessionKey)) {
                break;
            }

            if ($previousMinute === $this->getCurrentMinute()) {
                continue;
            }

            $previousMinute = $this->getCurrentMinute();

            Artisan::call('schedule:run', outputBuffer: $this->output);
        }

        return self::SUCCESS;
    }

    private function getCurrentMinute(): int
    {
        return (int) date('i');
    }
}
