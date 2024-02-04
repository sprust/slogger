<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaIntegerProperty extends BaseOaProperty
{
    const FORMAT_INT32 = 'int32';
    const FORMAT_INT64 = 'int64';

    public ?int $minimum = null;
    public ?int $maximum = null;
    public ?int $multipleOf = null;
    public ?bool $exclusiveMinimum = null;
    public ?bool $exclusiveMaximum = null;
    public ?int $greater = null;
    public ?int $less = null;
    public ?array $enum = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Integer;
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'minimum', $this->minimum);
        $this->addToArrayIfNotNull($data, 'maximum', $this->maximum);
        $this->addToArrayIfNotNull($data, 'multipleOf', $this->multipleOf);
        $this->addToArrayIfNotNull($data, 'exclusiveMinimum', $this->exclusiveMinimum);
        $this->addToArrayIfNotNull($data, 'exclusiveMaximum', $this->exclusiveMaximum);

        if (!is_null($this->enum)) {
            $this->addToArrayIfNotNull(
                $data,
                'enum',
                array_map(
                    fn(mixed $value) => is_string($value) ? intval(trim($value, ' "')) : intval($value),
                    $this->enum
                )
            );
        }

        return $data;
    }
}
