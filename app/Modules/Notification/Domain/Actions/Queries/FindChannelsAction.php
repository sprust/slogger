<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Repositories\ChannelRepository;
use App\Modules\Notification\Repositories\Dto\ChannelDto;

readonly class FindChannelsAction
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private ChannelFactory $channelFactory
    ) {
    }

    /**
     * @return ChannelObject[]
     */
    public function handle(?bool $enabled = null): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn(ChannelDto $dto): ?ChannelObject => $this->channelFactory->make($dto),
                    $this->channelRepository->find($enabled)
                )
            )
        );
    }
}
