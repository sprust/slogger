<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Properties;

use BackedEnum;
use Ifksco\OpenApiGenerator\Objects\Json\OaTypeEnum;
use RuntimeException;

class OaEnumProperty extends BaseOaProperty
{
    /** @var array<int|string> */
    public array $enum = [];
    /** @var class-string<BackedEnum>|null */
    public ?string $enumClass = null;

    public function getEnumType(): OaTypeEnum
    {
        $enumItemsType = gettype($this->enum[0]);

        return match ($enumItemsType) {
            'integer' => OaTypeEnum::Integer,
            'string'  => OaTypeEnum::String,
            default   => throw new RuntimeException("Type '$enumItemsType' not implemented!"),
        };
    }

    // STUB
    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::String;
    }

    protected function propertiesToArray(): array
    {
        if (!$this->enum) {
            throw new RuntimeException("Property 'enum' is empty");
        }

        $data = [];

        if ($this->enumClass) {
            $reference = ['$ref' => '#/components/schemas/' . class_basename($this->enumClass)];

            if ($this->nullable) {
                return [
                    'oneOf' => [
                        $reference,
                        [
                            'type'     => $this->getEnumType()->value,
                            'nullable' => true,
                            'enum'     => [null],
                        ],
                    ],
                ];
            }

            return $reference;
        }

        $this->addToArrayIfNotNull($data, 'type', $this->getEnumType());
        $this->addToArrayIfNotNull(
            $data,
            'enum',
            $this->nullable ? [...$this->enum, null] : $this->enum
        );

        return $data;
    }
}
