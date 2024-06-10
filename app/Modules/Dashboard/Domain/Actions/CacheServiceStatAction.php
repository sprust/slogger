<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Actions\Interfaces\CacheServiceStatActionInterface;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;
use App\Modules\Dashboard\Domain\Entities\Transports\ServiceStatTransport;
use App\Modules\Dashboard\Domain\Services\ServiceStatCache;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Domain\Entities\Objects\ServiceObject as ServiceServiceObject;
use Illuminate\Support\Collection;

readonly class CacheServiceStatAction implements CacheServiceStatActionInterface
{
    public function __construct(
        private ServiceStatRepositoryInterface $serviceStatRepository,
        private FindServicesAction $findServicesAction,
        private ServiceStatCache $serviceStatCache
    ) {
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
            ->map(fn(ServiceServiceObject $dto) => new ServiceObject(
                id: $dto->id,
                name: $dto->name
            ))
            ->keyBy(fn(ServiceObject $dto) => $dto->id);

        $result = [];

        foreach ($stats as $stat) {
            $service = $services->get($stat->serviceId);

            $result[] = ServiceStatTransport::toObject(
                dto: $stat,
                service: $service
            );
        }

        $this->serviceStatCache->put($result);
    }
}
