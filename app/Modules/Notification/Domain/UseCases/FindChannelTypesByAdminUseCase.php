<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelTypesAction;

readonly class FindChannelTypesByAdminUseCase
{
    public function __construct(
        private FindChannelTypesAction $findChannelTypesAction
    ) {
    }

    /**
     * @return ChannelTypeObject[]
     */
    public function handle(): array
    {
        return $this->findChannelTypesAction->handle();
    }
}
