<?php

namespace Ifksco\OpenApiGenerator\Console;

use Ifksco\OpenApiGenerator\OaService;
use Illuminate\Console\Command;
use ReflectionException;

class OaGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oa:generate {--public}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate open-api json schemes';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $public = $this->option('public');

        OaService::generateScheme($public);

        $this->info('Json schemes have been generated!');

        return 0;
    }
}
