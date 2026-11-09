<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Providers;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerRegistry;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\ServiceProvider;

abstract class BaseServiceProvider extends ServiceProvider
{
    abstract public static function setSchedule(Schedule $schedule): void;

    /** @return list<class-string<Command>> */
    abstract protected function getCommands(): array;

    /** @return list<class-string<ShouldQueue>> */
    abstract protected function getJobs(): array;

    /** @return array<class-string, list<class-string>> */
    abstract protected function getListeners(): array;

    /** @return class-string<ExceptionManagerInterface>|null */
    abstract protected function getExceptionManagerClass(): ?string;

    public function register(): void
    {
        foreach ($this->getContracts() as $interface => $implementation) {
            $this->app->singleton(
                abstract: is_int($interface) ? $implementation : $interface,
                concrete: is_int($interface) ? null : $implementation,
            );
        }

        $this->onRegister();
    }

    public function boot(): void
    {
        $this->onBoot();

        $eventDispatcher = $this->app->make(EventDispatcher::class);

        foreach ($this->getListeners() as $eventClass => $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                $eventDispatcher->listen(
                    events: $eventClass,
                    listener: $listenerClass,
                );
            }
        }

        if ($this->app->runningInConsole()) {
            $commands = $this->getCommands();

            if (count($commands) > 0) {
                $this->commands($commands);
            }
        }

        $jobs = $this->getJobs();

        if (count($jobs) > 0) {
            $this->app->make(DispatcherInterface::class)->registerJobs($jobs);
        }

        $exceptionManagerClass = $this->getExceptionManagerClass();

        if ($exceptionManagerClass !== null) {
            $exceptionManagerRegistry = $this->app->make(ExceptionManagerRegistry::class);
            $exceptionManager         = $this->app->make($exceptionManagerClass);

            $exceptionManagerRegistry->register($exceptionManager);
        }
    }

    protected function onRegister(): void
    {
    }

    protected function onBoot(): void
    {
    }

    /** @return array<int|class-string<object>, class-string<object>> */
    protected function getContracts(): array
    {
        return [];
    }
}
