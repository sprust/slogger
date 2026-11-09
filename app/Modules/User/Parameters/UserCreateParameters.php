<?php

declare(strict_types=1);

namespace App\Modules\User\Parameters;

use JsonSerializable;

readonly class UserCreateParameters implements JsonSerializable
{
    public function __construct(
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $password
    ) {
    }

    /** @return array{firstName: string, lastName: ?string, email: string, password: string} */
    public function jsonSerialize(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'email'     => $this->email,
            'password'  => '|hidden|',
        ];
    }
}
