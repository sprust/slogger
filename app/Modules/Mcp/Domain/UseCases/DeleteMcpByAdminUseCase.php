<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Domain\Actions\Mutations\DeleteMcpAction;

readonly class DeleteMcpByAdminUseCase
{
    public function __construct(private DeleteMcpAction $deleteMcpAction)
    {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(int $id): void
    {
        $this->deleteMcpAction->handle($id);
    }
}
