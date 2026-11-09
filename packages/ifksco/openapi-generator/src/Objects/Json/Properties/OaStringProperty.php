<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Properties;

use Ifksco\OpenApiGenerator\Objects\Json\OaTypeEnum;

class OaStringProperty extends BaseOaProperty
{
    public const FORMAT_BYTE     = 'byte';
    public const FORMAT_BINARY   = 'binary';
    public const FORMAT_DATE     = 'date';
    public const FORMAT_DATETIME = 'date-time';
    public const FORMAT_PASSWORD = 'password';
    public const FORMAT_EMAIL    = 'email';
    public const FORMAT_PHONE    = 'phone';
    public const FORMAT_UUID     = 'uuid';
    public const FORMAT_URI      = 'uri';
    public const FORMAT_HOSTNAME = 'hostname';
    public const FORMAT_IPV4     = 'ipv4';
    public const FORMAT_IPV6     = 'ipv6';

    public ?int $maxLength  = null;
    public ?int $minLength  = null;
    public ?string $pattern = null;
    public ?array $enum     = null;

    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::String;
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'maxLength', $this->maxLength);
        $this->addToArrayIfNotNull($data, 'minLength', $this->minLength);
        $this->addToArrayIfNotNull($data, 'pattern', $this->pattern);

        if (!is_null($this->enum)) {
            $this->addToArrayIfNotNull($data, 'enum', array_map('strval', $this->enum));
        }

        return $data;
    }
}
