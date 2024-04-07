<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Adapters\Service\ServiceAdapter;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceStatObject;
use App\Modules\Dashboard\Domain\Entities\Transports\ServiceStatTransport;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
use Illuminate\Support\Collection;

readonly class FindServiceStatAction
{
    public function __construct(
        private ServiceStatRepositoryInterface $serviceStatRepository,
        private ServiceAdapter $serviceAdapter
    ) {
    }

    /**
     * @return ServiceStatObject[]
     */
    public function handle(): array
    {
        $stats = $this->serviceStatRepository->find();

        if (empty($stats)) {
            return [];
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

        return $result;
    }
}
