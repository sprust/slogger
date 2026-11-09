<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use JsonSerializable;

readonly class CloseIncidentByAdminParameters implements JsonSerializable
{
    public function __construct(public string $incidentId, public ?string $token)
    {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'incidentId' => $this->incidentId,
            'token'      => '|hidden|',
        ];
    }
}
