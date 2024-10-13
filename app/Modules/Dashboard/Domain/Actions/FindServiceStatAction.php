<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Contracts\Actions\CacheServiceStatActionInterface;
use App\Modules\Dashboard\Contracts\Actions\FindServiceStatActionInterface;
use App\Modules\Dashboard\Domain\Services\ServiceStatCache;

readonly class FindServiceStatAction implements FindServiceStatActionInterface
{
    public function __construct(
        private ServiceStatCache $serviceStatCache,
        private CacheServiceStatActionInterface $cacheServiceStatAction
    ) {
    }

    public function handle(): array
    {
        if (!$this->serviceStatCache->has()) {
            $this->cacheServiceStatAction->handle();
        }

        return $this->serviceStatCache->get() ?? [];
    }
}
