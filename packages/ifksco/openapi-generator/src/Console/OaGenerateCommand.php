<?php

namespace Ifksco\OpenApiGenerator\Console;

use Ifksco\OpenApiGenerator\OaService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;

class OaGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oa:generate {diskAlias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate open-api json schemes';

    /**
     * @throws BindingResolutionException
     */
    public function handle(OaService $oaService): int
    {
        $oaService->generateScheme(
            $this->argument('diskAlias')
        );

        $this->info('Json schemes have been generated!');

        return 0;
    }
}
