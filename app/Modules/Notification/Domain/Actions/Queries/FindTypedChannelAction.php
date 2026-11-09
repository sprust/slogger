<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class FindTypedChannelAction
{
    public function __construct(private FindChannelAction $findChannelAction)
    {
    }

    /** @throws NotificationChannelNotFoundException */
    public function handle(int $id, NotificationChannelTypeEnum $type): ChannelObject
    {
        $channel = $this->findChannelAction->handle($id);

        if ($channel === null || $channel->type !== $type) {
            throw new NotificationChannelNotFoundException($id);
        }

        return $channel;
    }
}
