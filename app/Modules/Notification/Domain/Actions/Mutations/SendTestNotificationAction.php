<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\SendResultObject;

readonly class SendTestNotificationAction
{
    public function __construct(
        private NotificationChannelTypeRegistry $types,
        private FindChannelAction $findChannelAction
    ) {
    }

    /**
     * @throws NotificationChannelNotFoundException
     */
    public function handle(int $channelId): SendResultObject
    {
        $channel = $this->findChannelAction->handle($channelId);

        if (is_null($channel)) {
            throw new NotificationChannelNotFoundException($channelId);
        }

        $sender = $this->types->for($channel->type)->sender();

        return $sender->send(
            channel: $channel,
            text: implode("\n", [
                '👋 ' . $sender->bold('SLogger'),
                'Test message from channel ' . $sender->bold($sender->escape($channel->name)) . '.',
                '',
                '✅ If you can read this, the channel works.',
            ])
        );
    }
}
