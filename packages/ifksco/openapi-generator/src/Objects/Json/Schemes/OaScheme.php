<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Schemes;

use Ifksco\OpenApiGenerator\Objects\Json\BaseOaJsonObject;
use Ifksco\OpenApiGenerator\Objects\Json\OaTypeEnum;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\BaseOaProperty;

class OaScheme extends BaseOaJsonObject
{
    /** @var array<string, BaseOaProperty> */
    public array $properties = [];

    /** @var array<string> */
    public array $required = [];

    /** @var array<OaScheme> */
    public array $oneOf = [];

    public ?bool $nullable = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Object;
    }

    protected function basePropertiesToArray(): array
    {
        return $this->propertiesToArray();
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        if ($this->properties) {
            $data['properties'] = $this->properties;
        }

        if ($this->required) {
            $data['required'] = $this->required;
        }

        if ($this->oneOf) {
            $data['oneOf'] = $this->oneOf;
        }

        return $data;
    }
}
