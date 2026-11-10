<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use App\Modules\Notification\Entities\IncidentEventGroupObject;
use JsonSerializable;

readonly class IncidentEventParameters implements JsonSerializable
{
    /**
     * @param array<string, mixed>|null  $payload
     * @param IncidentEventGroupObject[] $groups
     */
    public function __construct(public ?array $payload, public array $groups)
    {
    }

    /** @return array{payload: string, groups: string} */
    public function jsonSerialize(): array
    {
        return [
            'payload' => '|hidden|',
            'groups'  => '|hidden|',
        ];
    }
}
