<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Mcp\Domain\UseCases\CreateMcpByAdminUseCase;
use App\Modules\Mcp\Domain\UseCases\DeleteMcpByAdminUseCase;
use App\Modules\Mcp\Domain\UseCases\RegenerateMcpTokenByAdminUseCase;
use App\Modules\Mcp\Domain\UseCases\UpdateMcpByAdminUseCase;
use App\Modules\Mcp\Domain\UseCases\FindMcpByAdminUseCase;
use App\Modules\Mcp\Domain\UseCases\FindMcpsByAdminUseCase;
use App\Modules\Mcp\Infrastructure\Http\Requests\CreateMcpRequest;
use App\Modules\Mcp\Infrastructure\Http\Requests\UpdateMcpRequest;
use App\Modules\Mcp\Infrastructure\Http\Resources\McpResource;
use App\Modules\Mcp\Parameters\CreateMcpParameters;
use App\Modules\Mcp\Parameters\UpdateMcpParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class McpController
{
    public function __construct(private FindMcpsByAdminUseCase $findMcpsByAdminUseCase, private FindMcpByAdminUseCase $findMcpByAdminUseCase, private CreateMcpByAdminUseCase $createMcpByAdminUseCase, private UpdateMcpByAdminUseCase $updateMcpByAdminUseCase, private RegenerateMcpTokenByAdminUseCase $regenerateMcpTokenByAdminUseCase, private DeleteMcpByAdminUseCase $deleteMcpByAdminUseCase)
    {
    }

    #[OaListItemTypeAttribute(McpResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return McpResource::collection($this->findMcpsByAdminUseCase->handle());
    }

    public function show(int $id): McpResource
    {
        $mcp = $this->findMcpByAdminUseCase->handle($id);

        return new McpResource($mcp);
    }

    public function create(CreateMcpRequest $request): McpResource
    {
        $validated = $request->validated();

        return new McpResource(
            $this->createMcpByAdminUseCase->handle(
                new CreateMcpParameters(
                    name: ArrayValueGetter::string(
                        data: $validated,
                        key: 'name',
                    ),
                )
            )
        );
    }

    public function update(int $id, UpdateMcpRequest $request): McpResource
    {
        $validated = $request->validated();

        $this->updateMcpByAdminUseCase->handle(
            new UpdateMcpParameters(
                id: $id,
                name: ArrayValueGetter::string(
                    data: $validated,
                    key: 'name',
                ),
                enabled: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'enabled',
                ),
            )
        );

        return $this->show($id);
    }

    public function regenerateToken(int $id): McpResource
    {
        $this->regenerateMcpTokenByAdminUseCase->handle($id);

        return $this->show($id);
    }

    public function delete(int $id): void
    {
        $this->deleteMcpByAdminUseCase->handle($id);
    }
}
