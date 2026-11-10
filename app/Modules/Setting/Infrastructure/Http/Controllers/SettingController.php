<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Setting\Domain\UseCases\FindSettingsByAdminUseCase;
use App\Modules\Setting\Domain\UseCases\ResetSettingByAdminUseCase;
use App\Modules\Setting\Domain\UseCases\UpdateSettingByAdminUseCase;
use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Infrastructure\Http\Requests\UpdateSettingRequest;
use App\Modules\Setting\Infrastructure\Http\Resources\SettingResource;
use App\Modules\Setting\Parameters\SettingUpdateParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class SettingController
{
    public function __construct(
        private FindSettingsByAdminUseCase $findSettingsByAdminUseCase,
        private UpdateSettingByAdminUseCase $updateSettingByAdminUseCase,
        private ResetSettingByAdminUseCase $resetSettingByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(SettingResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return SettingResource::collection(
            $this->findSettingsByAdminUseCase->handle()
        );
    }

    public function update(string $key, UpdateSettingRequest $request): void
    {
        $validated = $request->validated();

        $this->updateSettingByAdminUseCase->handle(
            new SettingUpdateParameters(
                key: SettingKeyEnum::from($key),
                value: ArrayValueGetter::int(
                    data: $validated,
                    key: 'value',
                ),
            )
        );
    }

    public function reset(string $key): void
    {
        $this->resetSettingByAdminUseCase->handle(SettingKeyEnum::from($key));
    }
}
