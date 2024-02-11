<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataCustomFieldObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTraceDataCustomFieldResponse extends AbstractApiResource
{
    private string $key;
    #[OaListItemTypeAttribute('string')]
    private array $values;

    public function __construct(TraceDataCustomFieldObject $customFieldObject)
    {
        parent::__construct($customFieldObject);

        $this->key    = $customFieldObject->key;
        $this->values = $customFieldObject->values;
    }
}
