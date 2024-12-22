<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\FindSettingsActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;

readonly class FindSettingsAction implements FindSettingsActionInterface
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(): array
    {
        return $this->settingRepository->find(
            orderByTypeAndOnlyData: true
        );
    }
}
