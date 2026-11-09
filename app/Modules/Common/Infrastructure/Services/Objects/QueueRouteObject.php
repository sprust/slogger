<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Services\Objects;

readonly class QueueRouteObject
{
    public function __construct(public string $connection, public string $queue, public bool $allowed)
    {
    }
}
