<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceDataObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDataNodeResponse extends AbstractApiResource
{
    private string $key;
    private string|bool|int|float|null $value;
    #[OaListItemTypeAttribute(TraceDataNodeResponse::class, isRecursive: true)]
    private ?array $children;

    public function __construct(TraceDataObject $nodeObject)
    {
        parent::__construct($nodeObject);

        $this->key      = $nodeObject->key;
        $this->value    = $nodeObject->value;
        $this->children = $nodeObject->children
            ? TraceDataNodeResponse::mapIntoMe($nodeObject->children)
            : null;
    }
}
