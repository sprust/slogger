<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Repositories\ChannelRepository;

readonly class FindChannelAction
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private ChannelFactory $channelFactory
    ) {
    }

    public function handle(int $id): ?ChannelObject
    {
        $dto = $this->channelRepository->findById($id);

        return is_null($dto) ? null : $this->channelFactory->make($dto);
    }
}
