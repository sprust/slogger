<?php

namespace Ifksco\OpenApiGenerator;

use Ifksco\OpenApiGenerator\Console\OaGenerateCommand;
use Illuminate\Support\ServiceProvider;

class OaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            OaGenerateCommand::class,
        ]);
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/oa-generator.php' => config_path('oa-generator.php'),
        ], ['ifksco-openapi-generator']);
    }
}
