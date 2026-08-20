<?php

declare(strict_types=1);

namespace SConcur\Laravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use SConcur\Laravel\Config\AsyncConfig;
use SConcur\Laravel\Console\ExtensionLoadCommand;
use SConcur\Laravel\Console\ExtensionStatusCommand;
use SConcur\Laravel\Console\HttpStartCommand;
use SConcur\Laravel\Console\MasterReloadCommand;
use SConcur\Laravel\Console\MasterStartCommand;
use SConcur\Laravel\Console\MasterStatusCommand;
use SConcur\Laravel\Console\MasterStopCommand;
use SConcur\Laravel\Events\AsyncDispatcher;
use SConcur\Laravel\Foundation\AsyncApplication;
use SConcur\Laravel\Routing\AsyncRouter;
use SConcur\Laravel\Translation\AsyncTranslator;
use SConcur\Laravel\View\AsyncViewFactory;

/**
 * Laravel service provider for the SConcur integration.
 *
 * Always: merges config and registers the artisan commands.
 * Only inside the HTTP worker process (argv = `artisan sconcur:servers:http:start`):
 * enables AsyncApplication scoped resolution and swaps config/events/router/
 * translator/view for their coroutine-safe adapters. This gating keeps
 * web/Octane/CLI/queue completely untouched.
 *
 * See docs/fiber-safe-laravel-bridge.ru.md.
 */
class SConcurServiceProvider extends ServiceProvider
{
    /** Adapters that flip into per-coroutine mode once the app is booted. */
    private const array BOOT_COMPLETED_ADAPTERS = ['config', 'events', 'router', 'translator', 'view'];

    /**
     * @throws BindingResolutionException
     */
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

        if ($this->isHttpWorker()) {
            $this->registerAsyncAdapters();
        }
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

    /** True only for the spawned HTTP worker process. */
    private function isHttpWorker(): bool
    {
        return ($_SERVER['argv'][1] ?? null) === HttpStartCommand::NAME;
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerAsyncAdapters(): void
    {
        $this->registerConfigAdapter();

        if ($this->app instanceof AsyncApplication) {
            $this->app->enableAsyncMode();
        }

        $this->registerEventDispatcherAdapter();
        $this->registerRouterAdapter();
        $this->registerTranslatorAdapter();
        $this->registerViewAdapter();

        // Flip the adapters from boot-time (shared) into per-coroutine mode once
        // every provider has booted.
        $this->app->booted(function ($app): void {
            foreach (self::BOOT_COMPLETED_ADAPTERS as $abstract) {
                $instance = $app->make($abstract);

                if (method_exists($instance, 'bootCompleted')) {
                    $instance->bootCompleted();
                }
            }

            if (class_exists(Model::class)) {
                Model::setEventDispatcher($app->make('events'));
            }
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerConfigAdapter(): void
    {
        // Swap the config repository before other providers read it.
        $original = $this->app->make('config');

        $this->app->instance('config', new AsyncConfig($original->all()));
    }

    private function registerEventDispatcherAdapter(): void
    {
        $this->app->singleton('events', fn($app) => new AsyncDispatcher($app));
    }

    private function registerRouterAdapter(): void
    {
        $this->app->singleton('router', fn($app) => new AsyncRouter($app['events'], $app));

        // A kernel resolved against the old router must be rebuilt with the new one.
        if ($this->app->resolved(Kernel::class)) {
            $this->app->forgetInstance(Kernel::class);
        }
    }

    private function registerTranslatorAdapter(): void
    {
        // extend() survives the deferred TranslationServiceProvider rebinding 'translator'.
        $this->app->extend('translator', function ($translator, $app) {
            $async = new AsyncTranslator($app['translation.loader'], $app->getLocale());
            $async->setFallback($app->getFallbackLocale());

            return $async;
        });
    }

    private function registerViewAdapter(): void
    {
        $this->app->singleton('view', function ($app) {
            $factory = new AsyncViewFactory(
                $app['view.engine.resolver'],
                $app['view.finder'],
                $app['events'],
            );

            $factory->setContainer($app);
            $factory->share('app', $app);

            return $factory;
        });
    }
}
