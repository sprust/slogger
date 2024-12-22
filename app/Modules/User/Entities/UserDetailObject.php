<?php

declare(strict_types=1);

namespace App\Modules\User\Entities;

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
