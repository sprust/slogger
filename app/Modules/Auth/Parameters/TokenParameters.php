<?php

declare(strict_types=1);

namespace App\Modules\Auth\Parameters;

use JsonSerializable;

readonly class TokenParameters implements JsonSerializable
{
    public function __construct(public string $token)
    {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'token' => '|hidden|',
        ];
    }
}
