<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerRegistry;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\ServiceProvider;

abstract class BaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->getContracts() as $interface => $implementation) {
            if (is_integer($interface)) {
                $this->app->singleton($implementation);
            } else {
                $this->app->singleton(
                    abstract: $interface,
                    concrete: $implementation,
                );
            }
        }
    }

    public function boot(): void
    {
        $exceptionManagerRegistry = $this->app->make(ExceptionManagerRegistry::class);

        foreach ($this->getExceptionManagers() as $exceptionManagerClass) {
            $exceptionManagerRegistry->register($this->app->make($exceptionManagerClass));
        }

        $jobClasses = $this->getJobs();

        if (count($jobClasses) > 0) {
            $this->app->make(DispatcherInterface::class)->registerJobs($jobClasses);
        }

        $eventDispatcher = $this->app->make(EventDispatcher::class);

        foreach ($this->getListeners() as $eventClass => $listenerClasses) {
            foreach ($listenerClasses as $listenerClass) {
                $eventDispatcher->listen(
                    events: $eventClass,
                    listener: $listenerClass,
                );
            }
        }
    }

    /**
     * @return array<class-string<ShouldQueue>>
     */
    protected function getJobs(): array
    {
        return [];
    }

    /**
     * @return array<class-string, array<class-string>>
     */
    protected function getListeners(): array
    {
        return [];
    }

    /**
     * @return array<int|class-string<object>, class-string<object>>
     */
    protected function getContracts(): array
    {
        return [];
    }

    /** @return list<class-string<ExceptionManagerInterface>> */
    protected function getExceptionManagers(): array
    {
        return [];
    }
}
