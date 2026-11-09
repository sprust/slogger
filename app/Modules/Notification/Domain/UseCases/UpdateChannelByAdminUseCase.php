<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Domain\Actions\Queries\FindTypedChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\UpdateChannelAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Parameters\UpdateChannelParameters;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class UpdateChannelByAdminUseCase
{
    public function __construct(private FindTypedChannelAction $findTypedChannelAction, private UpdateChannelAction $updateChannelAction)
    {
    }

    /** @throws NotificationChannelNotFoundException */
    public function handle(UpdateChannelParameters $parameters, NotificationChannelTypeEnum $type): void
    {
        $this->findTypedChannelAction->handle(
            id: $parameters->id,
            type: $type,
        );

        $this->updateChannelAction->handle($parameters);
    }
}
