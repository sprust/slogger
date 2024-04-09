<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Entities\Objects\ServiceStatObject;
use App\Modules\Dashboard\Domain\Services\ServiceStatCache;

readonly class FindServiceStatAction
{
    public function __construct(
        private ServiceStatCache $serviceStatCache,
        private CacheServiceStatAction $cacheServiceStatAction
    ) {
    }

    /**
     * @return ServiceStatObject[]
     */
    public function handle(): array
    {
        if (!$this->serviceStatCache->has()) {
            $this->cacheServiceStatAction->handle();
        }

        return $this->serviceStatCache->get() ?? [];
    }
}
