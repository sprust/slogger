<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;

readonly class FindMcpByAdminUseCase
{
    public function __construct(private FindMcpAction $findMcpAction)
    {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(int $id): McpObject
    {
        return $this->findMcpAction->handle($id) ?? throw new McpNotFoundException($id);
    }
}
