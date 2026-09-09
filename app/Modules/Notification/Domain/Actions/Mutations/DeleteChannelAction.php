<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Repositories\ChannelRepository;

readonly class DeleteChannelAction
{
    public function __construct(
        private ChannelRepository $channelRepository
    ) {
    }

    public function handle(int $channelId): void
    {
        $this->channelRepository->delete($channelId);
    }
}
