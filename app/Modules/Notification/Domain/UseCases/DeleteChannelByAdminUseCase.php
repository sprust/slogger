<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Domain\Actions\Mutations\DeleteChannelAction;

readonly class DeleteChannelByAdminUseCase
{
    public function __construct(private DeleteChannelAction $deleteChannelAction)
    {
    }

    public function handle(int $channelId): void
    {
        $this->deleteChannelAction->handle($channelId);
    }
}
