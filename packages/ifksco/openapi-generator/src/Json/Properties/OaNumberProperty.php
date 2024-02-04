<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaNumberProperty extends BaseOaProperty
{
    const FORMAT_FLOAT  = 'float';
    const FORMAT_DOUBLE = 'double';

    public ?float $minimum = null;
    public ?float $maximum = null;
    public ?float $multipleOf = null;
    public ?bool $exclusiveMinimum = null;
    public ?bool $exclusiveMaximum = null;
    public ?float $greater = null;
    public ?float $less = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Number;
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'minimum', $this->minimum);
        $this->addToArrayIfNotNull($data, 'maximum', $this->maximum);
        $this->addToArrayIfNotNull($data, 'multipleOf', $this->multipleOf);
        $this->addToArrayIfNotNull($data, 'exclusiveMinimum', $this->exclusiveMinimum);
        $this->addToArrayIfNotNull($data, 'exclusiveMaximum', $this->exclusiveMaximum);
        $this->addToArrayIfNotNull($data, 'greater', $this->greater);
        $this->addToArrayIfNotNull($data, 'less', $this->less);

        return $data;
    }
}
