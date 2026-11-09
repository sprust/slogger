<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use App\Modules\Notification\Enums\IncidentStatusEnum;
use Illuminate\Support\Carbon;

readonly class IncidentSnapshotParameters
{
    public function __construct(public string $id, public int $watcherId, public IncidentStatusEnum $status, public Carbon $firstEventAt, public Carbon $lastEventAt, public int $eventsCount, public ?Carbon $closedAt, public ?int $closedByUserId)
    {
    }
}
