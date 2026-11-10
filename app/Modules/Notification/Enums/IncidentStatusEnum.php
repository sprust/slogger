<?php

declare(strict_types=1);

namespace App\Modules\Notification\Enums;

/** Past tense, both of them: a status names what happened to the incident, not how it looks. */
enum IncidentStatusEnum: string
{
    case Opened = 'opened';

    case Closed = 'closed';
}
