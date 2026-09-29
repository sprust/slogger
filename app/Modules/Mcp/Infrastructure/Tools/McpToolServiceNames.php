<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Service\Entities\ServiceObject;

readonly class McpToolServiceNames
{
    /**
     * @param ServiceObject[] $services
     */
    public function __construct(
        private array $services
    ) {
    }

    /**
     * @return array<string, int|string>|null
     */
    public function describe(?int $serviceId): ?array
    {
        if (is_null($serviceId)) {
            return null;
        }

        foreach ($this->services as $service) {
            if ($service->id === $serviceId) {
                return [
                    'id'   => $service->id,
                    'name' => $service->name,
                ];
            }
        }

        return [
            'id'   => $serviceId,
            'name' => '',
        ];
    }
}
