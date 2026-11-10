<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Domain\Actions\Mutations\RegenerateMcpTokenAction;

readonly class RegenerateMcpTokenByAdminUseCase
{
    public function __construct(
        private RegenerateMcpTokenAction $regenerateMcpTokenAction
    ) {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(int $id): void
    {
        $this->regenerateMcpTokenAction->handle($id);
    }
}
