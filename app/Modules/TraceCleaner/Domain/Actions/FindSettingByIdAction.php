<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\TraceCleaner\Domain\Entities\Transports\SettingTransport;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;

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
