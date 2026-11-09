<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\FindTraceTreeByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceTreeChildrenByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceTreeContentByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceTreeFilteredByAdminUseCase;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeChildrenRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeContentRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeFilterRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeTreeRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeChildrenResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeFilteredResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResponse;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeContentResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class TraceTreeController
{
    public function __construct(private FindTraceTreeByAdminUseCase $findTraceTreeByAdminUseCase, private FindTraceTreeContentByAdminUseCase $findTraceTreeContentByAdminUseCase, private FindTraceTreeChildrenByAdminUseCase $findTraceTreeChildrenByAdminUseCase, private FindTraceTreeFilteredByAdminUseCase $findTraceTreeFilteredByAdminUseCase)
    {
    }

    /**
     * @throws ClickhouseQueryException
     */
    #[OaListItemTypeAttribute(TraceTreeResource::class)]
    public function tree(TraceTreeTreeRequest $request): TraceTreeResponse
    {
        $validated = $request->validated();
        $traceTree = $this->findTraceTreeByAdminUseCase->handle(
            traceId: ArrayValueGetter::string(
                data: $validated,
                key: 'trace_id',
            ),
            fresh: ArrayValueGetter::bool(
                data: $validated,
                key: 'fresh',
            ),
            isChild: ArrayValueGetter::bool(
                data: $validated,
                key: 'is_child',
            ),
        );

        return new TraceTreeResponse($traceTree);
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function content(TraceTreeContentRequest $request): TraceTreeContentResource
    {
        $validated        = $request->validated();
        $traceTreeObjects = $this->findTraceTreeContentByAdminUseCase->handle(
            traceId: ArrayValueGetter::string(
                data: $validated,
                key: 'trace_id',
            ),
            isChild: ArrayValueGetter::bool(
                data: $validated,
                key: 'is_child',
            ),
        );

        return new TraceTreeContentResource($traceTreeObjects);
    }

    public function children(TraceTreeChildrenRequest $request): TraceTreeChildrenResource
    {
        $validated = $request->validated();

        return new TraceTreeChildrenResource(
            $this->findTraceTreeChildrenByAdminUseCase->handle(
                rootTraceId: ArrayValueGetter::string(
                    data: $validated,
                    key: 'root_trace_id',
                ),
                parentTraceId: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'parent_trace_id',
                ),
                cursor: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'cursor',
                ),
                limit: ArrayValueGetter::intNull(
                    data: $validated,
                    key: 'limit',
                ) ?? TraceTreeChildrenRequest::DEFAULT_LIMIT,
            )
        );
    }

    public function filter(TraceTreeFilterRequest $request): TraceTreeFilteredResource
    {
        $validated = $request->validated();

        return new TraceTreeFilteredResource(
            $this->findTraceTreeFilteredByAdminUseCase->handle(
                rootTraceId: ArrayValueGetter::string(
                    data: $validated,
                    key: 'root_trace_id',
                ),
                parameters: new TraceTreeFilterParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull(
                        data: $validated,
                        key: 'service_ids',
                    ) ?? [],
                    types: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'types',
                    ) ?? [],
                    tags: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'tags',
                    ) ?? [],
                    statuses: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'statuses',
                    ) ?? [],
                ),
            )
        );
    }
}
