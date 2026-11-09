<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

class CreatedController
{
    public function __invoke(): CountResource
    {
        return new CountResource(null);
    }
}
