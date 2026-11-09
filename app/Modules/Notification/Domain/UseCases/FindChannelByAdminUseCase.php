<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Domain\Actions\Queries\FindTypedChannelAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class FindChannelByAdminUseCase
{
    public function __construct(private FindTypedChannelAction $findTypedChannelAction)
    {
    }

    /** @throws NotificationChannelNotFoundException */
    public function handle(int $id, NotificationChannelTypeEnum $type): ChannelObject
    {
        return $this->findTypedChannelAction->handle(
            id: $id,
            type: $type,
        );
    }
}
