<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\TraceCleaner\Domain\Entities\Transports\SettingTransport;
use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class FindSettingsAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    /**
     * @return SettingObject[]
     */
    public function handle(): array
    {
        return array_map(
            fn(SettingDto $settingDto) => SettingTransport::toObject($settingDto),
            $this->settingRepository->find()
        );
    }
}
