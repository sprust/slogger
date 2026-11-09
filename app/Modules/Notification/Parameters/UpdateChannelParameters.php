<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use JsonSerializable;

readonly class UpdateChannelParameters implements JsonSerializable
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public array $settings
    ) {
    }

    /** @return array{id: int, name: string, enabled: bool, settings: string} */
    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'enabled'  => $this->enabled,
            'settings' => '|hidden|',
        ];
    }
}
