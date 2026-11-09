<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use JsonSerializable;

readonly class CreateChannelParameters implements JsonSerializable
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public string $name,
        public NotificationChannelTypeEnum $type,
        public bool $enabled,
        public array $settings
    ) {
    }

    /** @return array{name: string, type: string, enabled: bool, settings: string} */
    public function jsonSerialize(): array
    {
        return [
            'name'     => $this->name,
            'type'     => $this->type->value,
            'enabled'  => $this->enabled,
            'settings' => '|hidden|',
        ];
    }
}
