<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;

class StringValueResource extends AbstractApiResource
{
    private string $value;

    public function __construct(string $value)
    {
        parent::__construct($value);

        $this->value = $value;
    }
}
