<?php

namespace App\Modules\Trace\Framework\Http\Resources\Data;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Data\TraceDataAdditionalFieldObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDataAdditionalFieldResource extends AbstractApiResource
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
