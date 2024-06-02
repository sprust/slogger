<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\SettingObject;
use App\Modules\Trace\Domain\Entities\Transports\SettingTransport;
use App\Modules\Trace\Repositories\Interfaces\SettingRepositoryInterface;

readonly class FindSettingByIdAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(int $settingId): ?SettingObject
    {
        $settingDto = $this->settingRepository->findOneById($settingId);

        if (!$settingDto) {
            return null;
        }

        return SettingTransport::toObject($settingDto);
    }
}
