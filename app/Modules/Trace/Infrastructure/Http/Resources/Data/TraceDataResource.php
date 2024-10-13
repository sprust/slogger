<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources\Data;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
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
