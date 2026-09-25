<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure;

use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use App\Modules\Common\Infrastructure\Services\MutexManager;

class CommonServiceProvider extends BaseServiceProvider
{
    protected function getContracts(): array
    {
        return [
            MutexManagerInterface::class => MutexManager::class,
        ];
    }
}
