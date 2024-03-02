<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceDataAdditionalFieldObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDataAdditionalFieldResponse extends AbstractApiResource
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
