<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeContentActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeContentRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeTreeRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResponse;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeContentResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

readonly class TraceTreeController
{
    public function __construct(
        private FindTraceTreeActionInterface $findTraceTreeAction,
        private FindTraceTreeContentActionInterface $findTraceTreeContentAction,
    ) {
    }

    #[OaListItemTypeAttribute(TraceTreeResource::class)]
    public function tree(TraceTreeTreeRequest $request): TraceTreeResponse
    {
        $validated = $request->validated();

        $traceTreeObjects = $this->findTraceTreeAction->handle(
            traceId: ArrayValueGetter::string($validated, 'trace_id'),
            fresh: ArrayValueGetter::bool($validated, 'fresh'),
            isChild: ArrayValueGetter::bool($validated, 'is_child'),
        );

        if (is_null($traceTreeObjects)) {
            abort(404, 'Trace not found');
        }

        return new TraceTreeResponse($traceTreeObjects);
    }

    public function content(TraceTreeContentRequest $request): TraceTreeContentResource
    {
        $validated = $request->validated();

        $traceTreeObjects = $this->findTraceTreeContentAction->handle(
            traceId: ArrayValueGetter::string($validated, 'trace_id'),
            isChild: ArrayValueGetter::bool($validated, 'is_child'),
        );

        return new TraceTreeContentResource($traceTreeObjects);
    }
}
