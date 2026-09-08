<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Types;

use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;

interface NotificationChannelTypeDefinitionInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function makeSettings(array $settings): ChannelSettingsInterface;

    /**
     * The settings to store after an edit. A secret field is never sent back to the form,
     * so it arrives empty from a dialog nobody touched, and the stored value stays.
     *
     * @param array<string, mixed> $submitted
     */
    public function makeUpdatedSettings(array $submitted, ChannelSettingsInterface $stored): ChannelSettingsInterface;

    public function describe(): ChannelTypeObject;

    public function sender(): NotificationSenderInterface;
}
