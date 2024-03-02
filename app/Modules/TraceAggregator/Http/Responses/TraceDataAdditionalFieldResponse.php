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

    public function __construct(TraceDataAdditionalFieldObject $additionalField)
    {
        parent::__construct($additionalField);

        $this->key    = $additionalField->key;
        $this->values = $additionalField->values;
    }
}
