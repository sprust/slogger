<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Contracts\Actions\CacheServiceStatActionInterface;
use App\Modules\Dashboard\Contracts\Repositories\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Domain\Services\ServiceStatCache;
use App\Modules\Dashboard\Entities\ServiceObject;
use App\Modules\Service\Entities\ServiceObject as ModuleServiceObject;
use App\Modules\Dashboard\Entities\ServiceStatObject;
use App\Modules\Service\Contracts\Actions\FindServicesActionInterface;
use Illuminate\Support\Collection;

readonly class CacheServiceStatAction implements CacheServiceStatActionInterface
{
    public function __construct(
        private ServiceStatRepositoryInterface $serviceStatRepository,
        private FindServicesActionInterface    $findServicesAction,
        private ServiceStatCache               $serviceStatCache
    )
    {
    }

    public function handle(): void
    {
        $stats = $this->serviceStatRepository->find();

        if (empty($stats)) {
            $this->serviceStatCache->flush();

            return;
        }

        /** @var Collection<int, ServiceObject> $services */
        $services = collect($this->findServicesAction->handle())
            ->keyBy(fn(ModuleServiceObject $service) => $service->id);

        $result = [];

        foreach ($stats as $stat) {
            $service = $services->get($stat->serviceId);

            $result[] = new ServiceStatObject(
                service: $service
                    ?: new ServiceObject(
                        id: $stat->serviceId,
                        name: 'UNKNOWN',
                    ),
                from: $stat->from,
                to: $stat->to,
                type: $stat->type,
                status: $stat->status,
                count: $stat->count,
            );
        }

        $this->serviceStatCache->put($result);
    }
}
