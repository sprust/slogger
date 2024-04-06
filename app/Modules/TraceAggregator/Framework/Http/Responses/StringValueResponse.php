<?php

namespace App\Modules\TraceAggregator\Framework\Http\Responses;

use App\Http\Resources\AbstractApiResource;

class StringValueResponse extends AbstractApiResource
{
    private string $value;

    public function __construct(string $value)
    {
        parent::__construct($value);

        $this->value = $value;
    }
}
