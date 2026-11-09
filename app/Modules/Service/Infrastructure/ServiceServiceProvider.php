<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure;

use App\Modules\Service\Infrastructure\Exceptions\ServiceExceptionManager;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Service\Domain\Actions\CreateServiceAction;
use App\Modules\Service\Domain\Actions\FindServiceAction;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Domain\Actions\SortServicesAction;
use App\Modules\Service\Domain\Actions\UpdateServiceAction;
use App\Modules\Service\Infrastructure\Commands\CreateServiceCommand;
use App\Modules\Service\Repositories\ServiceRepository;
use Illuminate\Console\Scheduling\Schedule;

class ServiceServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
    }

    protected function getCommands(): array
    {
        return [CreateServiceCommand::class];
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            ServiceRepository::class,
            // actions
            CreateServiceAction::class,
            FindServicesAction::class,
            FindServiceAction::class,
            UpdateServiceAction::class,
            SortServicesAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return ServiceExceptionManager::class;
    }

    protected function getJobs(): array
    {
        return [];
    }

    protected function getListeners(): array
    {
        return [];
    }
}
