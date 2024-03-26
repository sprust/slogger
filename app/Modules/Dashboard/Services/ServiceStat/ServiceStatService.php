<?php

namespace App\Modules\Dashboard\Services\ServiceStat;

use App\Modules\Dashboard\Adapters\Dto\ServiceDto as AdapterServiceDto;
use App\Modules\Dashboard\Adapters\ServiceAdapter;
use App\Modules\Dashboard\Repositories\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Services\ServiceStat\Objects\ServiceStatObject;
use App\Modules\Dashboard\Services\ServiceStat\Objects\ServiceStatServiceObject;
use Illuminate\Support\Collection;

readonly class ServiceStatService
{
    public function __construct(
        private ServiceStatRepositoryInterface $serviceStatRepository,
        private ServiceAdapter $serviceAdapter
    ) {
    }

    /**
     * @return ServiceStatObject[]
     */
    public function find(): array
    {
        $stats = $this->serviceStatRepository->find();

        if (empty($stats)) {
            return [];
        }

        /** @var Collection<int, AdapterServiceDto> $services */
        $services = collect($this->serviceAdapter->find())
            ->keyBy(fn(AdapterServiceDto $serviceDto) => $serviceDto->id);

        $result = [];

        foreach ($stats as $stat) {
            $service = $services->get($stat->serviceId);

            $result[] = new ServiceStatObject(
                service: new ServiceStatServiceObject(
                    id: $stat->serviceId,
                    name: $service?->name ?? 'UNKNOWN',
                ),
                from: $stat->from,
                to: $stat->to,
                type: $stat->type,
                status: $stat->status,
                count: $stat->count,
            );
        }

        return $result;
    }
}
