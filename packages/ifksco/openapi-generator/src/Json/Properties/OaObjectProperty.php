<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaObjectProperty extends BaseOaProperty
{
    public ?int $minProperties = null;
    public ?int $maxProperties = null;

    /** @var array<string, BaseOaProperty>|null */
    public ?array $properties = null;

    /** @var array<string>|null */
    public ?array $requiredProperties = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Object;
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'properties', $this->properties);
        $this->addToArrayIfNotNull($data, 'minProperties', $this->minProperties);
        $this->addToArrayIfNotNull($data, 'maxProperties', $this->maxProperties);
        $this->addToArrayIfNotNull($data, 'required', $this->requiredProperties);

        return $data;
    }
}
