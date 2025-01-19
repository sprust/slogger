<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;

class StartDispatcherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slogger:dispatcher:start {dispatcher?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for start dispatcher';

    /**
     * Execute the console command.
     *
     * @throws BindingResolutionException
     */
    public function handle(DispatcherFactory $dispatcherFactory): void
    {
        $dispatcher = $this->argument('dispatcher') ?: config('slogger.dispatchers.default');

        $dispatcherFactory->create($dispatcher)->start($this->output);
    }
}
