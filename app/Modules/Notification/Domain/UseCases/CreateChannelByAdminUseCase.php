<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Parameters\CreateChannelParameters;
use App\Modules\Notification\Domain\Actions\Mutations\CreateChannelAction;

readonly class CreateChannelByAdminUseCase
{
    public function __construct(private CreateChannelAction $createChannelAction)
    {
    }

    public function handle(CreateChannelParameters $parameters): ChannelObject
    {
        return $this->createChannelAction->handle($parameters);
    }
}
