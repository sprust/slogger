<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceServicesObject
{
    private array $servicesKeById;

    /**
     * @param TraceServiceObject[] $services
     */
    public function __construct(
        array $services
    ) {
        $servicesKeById = [];

        foreach ($services as $service) {
            $servicesKeById[$service->id] = $service;
        }

        $this->servicesKeById = $servicesKeById;
    }

    public function getById(int $id): ?TraceServiceObject
    {
        return $this->servicesKeById[$id] ?? null;
    }
}
