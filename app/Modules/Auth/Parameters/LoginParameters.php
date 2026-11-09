<?php

declare(strict_types=1);

namespace App\Modules\Auth\Parameters;

use JsonSerializable;

readonly class LoginParameters implements JsonSerializable
{
    public function __construct(public string $email, public string $password)
    {
    }

    /** @return array{email: string, password: string} */
    public function jsonSerialize(): array
    {
        return [
            'email'    => $this->email,
            'password' => '|hidden|',
        ];
    }
}
