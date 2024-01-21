<?php

namespace App\Modules\Projects\Repository\Parameters;

readonly class ProjectsCreateParameters
{
    public function __construct(
        public int $userId,
        public string $name
    ) {
    }
}
