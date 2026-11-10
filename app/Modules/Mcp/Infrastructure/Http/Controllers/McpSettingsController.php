<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Controllers;

use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use App\Modules\Mcp\Domain\UseCases\FindMcpSettingsByAdminUseCase;
use App\Modules\Mcp\Infrastructure\Http\Resources\McpSettingsResource;

readonly class McpSettingsController
{
    public function __construct(
        private FindMcpSettingsByAdminUseCase $findMcpSettingsByAdminUseCase
    ) {
    }

    /**
     * @throws McpServerNameInvalidException
     */
    public function show(): McpSettingsResource
    {
        return new McpSettingsResource(
            $this->findMcpSettingsByAdminUseCase->handle()
        );
    }
}
