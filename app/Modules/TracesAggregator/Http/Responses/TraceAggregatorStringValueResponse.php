<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;

class TraceAggregatorStringValueResponse extends AbstractApiResource
{
    private string $value;

    public function __construct(string $value)
    {
        parent::__construct($value);

        $this->value = $value;
    }
}
