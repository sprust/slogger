<?php

namespace App\Modules\User\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class UserDetailDto
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
