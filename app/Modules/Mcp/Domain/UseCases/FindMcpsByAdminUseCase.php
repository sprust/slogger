<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpsAction;

readonly class FindMcpsByAdminUseCase
{
    public function __construct(private FindMcpsAction $findMcpsAction)
    {
    }

    /**
     * @return McpObject[]
     */
    public function handle(): array
    {
        return $this->findMcpsAction->handle();
    }
}
