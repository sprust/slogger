<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Entities\Transports\SettingTransport;
use App\Modules\Cleaner\Repositories\Dto\SettingDto;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

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
