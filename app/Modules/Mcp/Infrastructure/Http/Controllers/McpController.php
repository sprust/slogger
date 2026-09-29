<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Mcp\Domain\Actions\Mutations\CreateMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\DeleteMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\RegenerateMcpTokenAction;
use App\Modules\Mcp\Domain\Actions\Mutations\UpdateMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpsAction;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Infrastructure\Http\Requests\CreateMcpRequest;
use App\Modules\Mcp\Infrastructure\Http\Requests\UpdateMcpRequest;
use App\Modules\Mcp\Infrastructure\Http\Resources\McpResource;
use App\Modules\Mcp\Parameters\CreateMcpParameters;
use App\Modules\Mcp\Parameters\UpdateMcpParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class McpController
{
    public function __construct(
        private FindMcpsAction $findMcpsAction,
        private FindMcpAction $findMcpAction,
        private CreateMcpAction $createMcpAction,
        private UpdateMcpAction $updateMcpAction,
        private RegenerateMcpTokenAction $regenerateMcpTokenAction,
        private DeleteMcpAction $deleteMcpAction
    ) {
    }

    #[OaListItemTypeAttribute(McpResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return McpResource::collection(
            $this->findMcpsAction->handle()
        );
    }

    public function show(int $id): McpResource
    {
        $mcp = $this->findMcpAction->handle($id);

        if (is_null($mcp)) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Mcp [$id] not found");
        }

        return new McpResource($mcp);
    }

    public function create(CreateMcpRequest $request): McpResource
    {
        $validated = $request->validated();

        return new McpResource(
            $this->createMcpAction->handle(
                new CreateMcpParameters(
                    name: ArrayValueGetter::string($validated, 'name')
                )
            )
        );
    }

    public function update(int $id, UpdateMcpRequest $request): McpResource
    {
        $validated = $request->validated();

        try {
            $this->updateMcpAction->handle(
                new UpdateMcpParameters(
                    id: $id,
                    name: ArrayValueGetter::string($validated, 'name'),
                    enabled: ArrayValueGetter::bool($validated, 'enabled')
                )
            );
        } catch (McpNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }

        return $this->show($id);
    }

    public function regenerateToken(int $id): McpResource
    {
        try {
            $this->regenerateMcpTokenAction->handle($id);
        } catch (McpNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }

        return $this->show($id);
    }

    public function delete(int $id): void
    {
        try {
            $this->deleteMcpAction->handle($id);
        } catch (McpNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }
    }
}
