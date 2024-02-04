<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaStringProperty extends BaseOaProperty
{
    const FORMAT_BYTE     = 'byte';
    const FORMAT_BINARY   = 'binary';
    const FORMAT_DATE     = 'date';
    const FORMAT_DATETIME = 'date-time';
    const FORMAT_PASSWORD = 'password';
    const FORMAT_EMAIL    = 'email';
    const FORMAT_PHONE    = 'phone';
    const FORMAT_UUID     = 'uuid';
    const FORMAT_URI      = 'uri';
    const FORMAT_HOSTNAME = 'hostname';
    const FORMAT_IPV4     = 'ipv4';
    const FORMAT_IPV6     = 'ipv6';

    public ?int $maxLength = null;
    public ?int $minLength = null;
    public ?string $pattern = null;
    public ?array $enum = null;

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
