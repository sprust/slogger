<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Entities\Bridges\McpServiceObject;

readonly class McpToolServiceNames
{
    /**
     * @param McpServiceObject[] $services
     */
    public function __construct(private array $services)
    {
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
