<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaEnumProperty extends BaseOaProperty
{
    /** @var array<string> */
    public array $enum = [];

    // STUB
    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::String;
    }

    protected function propertiesToArray(): array
    {
        if (!$this->enum) {
            throw new \RuntimeException("Property 'enum' is empty");
        }

        $enumItemsType = gettype($this->enum[0]);

        $type = match ($enumItemsType) {
            'integer' => OaTypeEnum::Integer,
            'string' => OaTypeEnum::String,
            default => throw new \RuntimeException("Type '$enumItemsType' not implemented!"),
        };

        $data = [];

        $this->addToArrayIfNotNull($data, 'type', $type);
        $this->addToArrayIfNotNull($data, 'enum', $this->enum);

        return $data;
    }
}
