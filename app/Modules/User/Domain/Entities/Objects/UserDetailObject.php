<?php

namespace App\Modules\User\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class UserDetailObject
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $password,
        public string $apiToken,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
