<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelsAction;

readonly class FindChannelsByAdminUseCase
{
    public function __construct(private FindChannelsAction $findChannelsAction)
    {
    }

    /**
     * @return ChannelObject[]
     */
    public function handle(?bool $enabled = null): array
    {
        return $this->findChannelsAction->handle(
            enabled: $enabled,
        );
    }
}
