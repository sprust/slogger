<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceDataObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDataResponse extends AbstractApiResource
{
    private string $key;
    private string|bool|int|float|null $value;
    #[OaListItemTypeAttribute(TraceDataResponse::class, isRecursive: true)]
    private ?array $children;

    public function __construct(TraceDataObject $data)
    {
        parent::__construct($data);

        $this->key      = $data->key;
        $this->value    = $data->value;
        $this->children = $data->children
            ? TraceDataResponse::mapIntoMe($data->children)
            : null;
    }
}
