<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities\Settings;

interface ChannelSettingsInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
