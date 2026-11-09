<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use App\Modules\Mcp\Entities\McpSettingsObject;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;

readonly class FindMcpSettingsByAdminUseCase
{
    public function __construct(private FindMcpSettingsAction $findMcpSettingsAction)
    {
    }

    /**
     * @throws McpServerNameInvalidException
     */
    public function handle(): McpSettingsObject
    {
        return $this->findMcpSettingsAction->handle();
    }
}
