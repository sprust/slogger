<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\SettingObject;
use App\Modules\Trace\Domain\Entities\Transports\SettingTransport;
use App\Modules\Trace\Repositories\Dto\SettingDto;
use App\Modules\Trace\Repositories\Interfaces\SettingRepositoryInterface;

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
