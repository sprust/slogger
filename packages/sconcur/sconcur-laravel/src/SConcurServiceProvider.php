<?php

declare(strict_types=1);

namespace SConcur\Laravel;

use Illuminate\Support\ServiceProvider;
use SConcur\Laravel\Console\ExtensionLoadCommand;
use SConcur\Laravel\Console\ExtensionStatusCommand;
use SConcur\Laravel\Console\HttpStartCommand;
use SConcur\Laravel\Console\MasterReloadCommand;
use SConcur\Laravel\Console\MasterStartCommand;
use SConcur\Laravel\Console\MasterStatusCommand;
use SConcur\Laravel\Console\MasterStopCommand;

/**
 * Laravel service provider for the SConcur integration.
 *
 * Skeleton: wires config and registers the (shell-only) artisan commands. The
 * coroutine-scoped application (AsyncApplication) is installed from the HTTP
 * worker bootstrap, not here, so requiring this package does not change
 * CLI / queue / Octane behavior.
 *
 * See docs/fiber-safe-laravel-bridge.ru.md.
 */
class SConcurServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sconcur.php', 'sconcur');

        $this->commands([
            MasterStartCommand::class,
            MasterStopCommand::class,
            MasterStatusCommand::class,
            MasterReloadCommand::class,
            HttpStartCommand::class,
            ExtensionLoadCommand::class,
            ExtensionStatusCommand::class,
        ]);

        // TODO: in async mode register scoped bindings / adapters
        // (AsyncRouter, AsyncTranslator, AsyncConfig, ...) — see docs §4.6.
    }

    public function boot(): void
    {
        $this->publishes(
            paths: [
                __DIR__ . '/../config/sconcur.php' => config_path('sconcur.php'),
            ],
            groups: [
                'sconcur-laravel',
            ]
        );
    }
}
