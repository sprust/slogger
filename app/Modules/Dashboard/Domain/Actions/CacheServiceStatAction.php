<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Adapters\Service\ServiceAdapter;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;
use App\Modules\Dashboard\Domain\Entities\Transports\ServiceStatTransport;
use App\Modules\Dashboard\Domain\Services\ServiceStatCache;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
use Illuminate\Support\Collection;

readonly class CacheServiceStatAction
{
    public function __construct(
        private ServiceStatRepositoryInterface $serviceStatRepository,
        private ServiceAdapter $serviceAdapter,
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
        $services = collect($this->serviceAdapter->find())
            ->keyBy(fn(ServiceObject $serviceDto) => $serviceDto->id);

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
