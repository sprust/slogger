<?php

declare(strict_types=1);

namespace App\Modules\User\Parameters;

readonly class UserUpdateParameters
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $lastName
    ) {
    }
}
