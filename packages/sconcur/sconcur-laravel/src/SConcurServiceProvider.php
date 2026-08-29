<?php

declare(strict_types=1);

namespace SConcur\Laravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use SConcur\Laravel\Config\AsyncConfig;
use SConcur\Laravel\Console\ExtensionLoadCommand;
use SConcur\Laravel\Console\ExtensionStatusCommand;
use Illuminate\Queue\QueueManager;
use SConcur\Laravel\Console\HttpStartCommand;
use SConcur\Laravel\Console\MasterReloadCommand;
use SConcur\Laravel\Console\MasterStartCommand;
use SConcur\Laravel\Console\MasterStatusCommand;
use SConcur\Laravel\Console\MasterStopCommand;
use SConcur\Laravel\Events\AsyncDispatcher;
use SConcur\Laravel\Console\RabbitmqConsumerStartCommand;
use SConcur\Laravel\Console\RabbitmqDeclareCommand;
use SConcur\Laravel\Console\TasksRestartCommand;
use SConcur\Laravel\Console\TasksStartCommand;
use SConcur\Laravel\Console\TasksStopCommand;
use SConcur\Laravel\Foundation\AsyncApplication;
use SConcur\Laravel\Queue\Rabbitmq\Connector;
use SConcur\Laravel\Routing\AsyncRouter;
use SConcur\Laravel\Tasks\Control\ControlChannel;
use SConcur\Laravel\Tasks\CooperativeSleeper;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TaskPoolOptions;
use SConcur\Laravel\Tasks\TaskRegistry;
use SConcur\Laravel\Translation\AsyncTranslator;
use SConcur\Laravel\View\AsyncViewFactory;

/**
 * Laravel service provider for the SConcur integration.
 *
 * Always: registers the artisan commands and the `sconcur_rabbitmq` queue connector.
 *
 * The config is published, not merged. Merging would leave the package's own defaults
 * standing behind the application's file, so a value the application deleted would
 * quietly come back — and the package would have to carry defaults for things only the
 * application knows, such as which queues to consume. Publish it with
 * `vendor:publish --tag=sconcur-laravel`; without it config('sconcur') is empty and the
 * commands say so rather than running on someone else's numbers.
 * Only inside a coroutine worker process (argv = `artisan sconcur:servers:http:start`
 * or `artisan sconcur:servers:rabbitmq:start`): enables AsyncApplication scoped
 * resolution and swaps config/events/router/
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
        $this->commands([
            MasterStartCommand::class,
            MasterStopCommand::class,
            MasterStatusCommand::class,
            MasterReloadCommand::class,
            HttpStartCommand::class,
            RabbitmqConsumerStartCommand::class,
            RabbitmqDeclareCommand::class,
            TasksStartCommand::class,
            TasksStopCommand::class,
            TasksRestartCommand::class,
            ExtensionLoadCommand::class,
            ExtensionStatusCommand::class,
        ]);

        $this->registerQueueConnector();
        $this->registerTaskPool();

        if ($this->isCoroutineWorker()) {
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

    /**
     * True only in a spawned worker whose handlers run concurrently — the HTTP server
     * and the queue-consumer pool. Everything else (web, CLI, queue:work) is untouched.
     */
    private function isCoroutineWorker(): bool
    {
        return in_array(
            $_SERVER['argv'][1] ?? null,
            [HttpStartCommand::NAME, RabbitmqConsumerStartCommand::NAME],
            true,
        );
    }

    /**
     * Registers the `sconcur_rabbitmq` queue driver.
     *
     * resolving() rather than resolving the manager here, so a request that never
     * touches a queue does not pay for building one.
     */
    private function registerQueueConnector(): void
    {
        $this->app->resolving('queue', static function (QueueManager $manager): void {
            $manager->addConnector('sconcur_rabbitmq', static fn(): Connector => new Connector());
        });
    }

    /**
     * Bindings of the periodic task pool.
     *
     * Everything is read from config('sconcur.tasks'), including the task list: the
     * registry resolves the classes named there out of the container, so a task is an
     * ordinary injectable service and the pool itself needs no knowledge of what it runs.
     */
    private function registerTaskPool(): void
    {
        $this->app->singleton(
            TaskPoolOptions::class,
            static fn(): TaskPoolOptions => TaskPoolOptions::fromArray((array) config('sconcur.tasks', [])),
        );

        $this->app->singleton(
            TaskRegistry::class,
            static fn(Container $app): TaskRegistry => new TaskRegistry(
                container: $app,
                list: (array) config('sconcur.tasks.list', []),
            ),
        );

        $this->app->singleton(
            CooperativeSleeper::class,
            static fn(Container $app): CooperativeSleeper => new CooperativeSleeper(
                chunkMs: $app->make(TaskPoolOptions::class)->sleepChunkMs,
            ),
        );

        $this->app->singleton(
            ControlChannel::class,
            static fn(Container $app): ControlChannel => new ControlChannel(
                cache: $app->make(CacheRepository::class),
                key: $app->make(TaskPoolOptions::class)->controlKey,
            ),
        );

        $this->app->singleton(TaskPoolLogger::class, static fn(): TaskPoolLogger => new TaskPoolLogger());
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
