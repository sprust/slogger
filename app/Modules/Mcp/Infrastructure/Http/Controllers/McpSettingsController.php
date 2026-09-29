<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Controllers;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Infrastructure\Http\Resources\McpSettingsResource;

readonly class McpSettingsController
{
    public function __construct(
        private FindMcpSettingsAction $findMcpSettingsAction
    ) {
    }

    public function show(): McpSettingsResource
    {
        return new McpSettingsResource(
            $this->findMcpSettingsAction->handle()
        );
    }
}
