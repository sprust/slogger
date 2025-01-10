<?php

namespace SLoggerLaravel\Commands;

use Illuminate\Console\Command;
use SLoggerLaravel\Dispatcher\Transporter\TransporterLoader;

class LoadTransporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slogger:transporter:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load transporter';

    public function handle(TransporterLoader $loader): int
    {
        $loader->load();

        return self::SUCCESS;
    }
}
