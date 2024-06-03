<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Entities\Transports\SettingTransport;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

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
