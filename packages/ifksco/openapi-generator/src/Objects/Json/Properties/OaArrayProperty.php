<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Properties;

use Ifksco\OpenApiGenerator\Objects\Json\BaseOaJsonObject;
use Ifksco\OpenApiGenerator\Objects\Json\OaTypeEnum;

class OaArrayProperty extends BaseOaProperty
{
    public ?int $minItems     = null;
    public ?int $maxItems     = null;
    public ?bool $uniqueItems = null;
    public bool $isPagination = false;

    public ?BaseOaJsonObject $items = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Array;
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'minItems', $this->minItems);
        $this->addToArrayIfNotNull($data, 'maxItems', $this->maxItems);
        $this->addToArrayIfNotNull($data, 'uniqueItems', $this->uniqueItems);
        $this->addToArrayIfNotNull($data, 'items', $this->items);

        return $data;
    }
}
