<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataAdditionalFieldObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTraceDataAdditionalFieldResponse extends AbstractApiResource
{
    private string $key;
    #[OaListItemTypeAttribute('string')]
    private array $values;

    public function __construct(TraceDataAdditionalFieldObject $additionalFieldObject)
    {
        parent::__construct($additionalFieldObject);

        $this->key    = $additionalFieldObject->key;
        $this->values = $additionalFieldObject->values;
    }
}
