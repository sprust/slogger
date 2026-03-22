<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceServicesObject;

readonly class FindTraceServicesAction
{
    public function __construct(private FindServicesAction $findServicesAction)
    {
    }

    /**
     * @param int[]|null $serviceIds
     */
    public function handle(?array $serviceIds = null): TraceServicesObject
    {
        if (!is_null($serviceIds) && !count($serviceIds)) {
            return new TraceServicesObject(services: []);
        }

        $services = $this->findServicesAction->handle(ids: $serviceIds);

        return new TraceServicesObject(
            services: array_map(
                fn(ServiceObject $service) => new TraceServiceObject(
                    id: $service->id,
                    name: $service->name,
                ),
                $services
            )
        );
    }
}
