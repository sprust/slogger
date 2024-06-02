<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDataObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDataResource extends AbstractApiResource
{
    private string $key;
    private string|bool|int|float|null $value;
    #[OaListItemTypeAttribute(TraceDataResource::class, isRecursive: true)]
    private ?array $children;

    public function __construct(TraceDataObject $data)
    {
        parent::__construct($data);

        $this->key      = $data->key;
        $this->value    = $data->value;
        $this->children = $data->children
            ? TraceDataResource::mapIntoMe($data->children)
            : null;
    }
}
