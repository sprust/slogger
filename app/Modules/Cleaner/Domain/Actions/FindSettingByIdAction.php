<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\FindSettingByIdActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Entities\SettingObject;

readonly class FindSettingByIdAction implements FindSettingByIdActionInterface
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(int $settingId): ?SettingObject
    {
        return $this->settingRepository->findOneById($settingId);
    }
}
