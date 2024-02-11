<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataNodeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTraceDataNodeResponse extends AbstractApiResource
{
    private string $key;
    private string|bool|int|float|null $value;
    #[OaListItemTypeAttribute(TraceAggregatorTraceDataNodeResponse::class, isRecursive: true)]
    private ?array $children;

    public function __construct(TraceDataNodeObject $nodeObject)
    {
        parent::__construct($nodeObject);

        $this->key      = $nodeObject->key;
        $this->value    = $nodeObject->value;
        $this->children = $nodeObject->children
            ?? TraceAggregatorTraceDataNodeResponse::mapIntoMe($nodeObject->children);
    }
}
