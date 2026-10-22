<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Setting\Domain\Actions\FindSettingsAction;
use App\Modules\Setting\Domain\Actions\ResetSettingAction;
use App\Modules\Setting\Domain\Actions\UpdateSettingAction;
use App\Modules\Setting\Domain\Exceptions\SettingValueOutOfRangeException;
use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Infrastructure\Http\Requests\UpdateSettingRequest;
use App\Modules\Setting\Infrastructure\Http\Resources\SettingResource;
use App\Modules\Setting\Parameters\SettingUpdateParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class SettingController
{
    public function __construct(
        private FindSettingsAction $findSettingsAction,
        private UpdateSettingAction $updateSettingAction,
        private ResetSettingAction $resetSettingAction
    ) {
    }

    #[OaListItemTypeAttribute(SettingResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return SettingResource::collection(
            $this->findSettingsAction->handle()
        );
    }

    public function update(string $key, UpdateSettingRequest $request): void
    {
        $validated = $request->validated();

        try {
            $this->updateSettingAction->handle(
                new SettingUpdateParameters(
                    key: SettingKeyEnum::from($key),
                    value: ArrayValueGetter::int($validated, 'value')
                )
            );
        } catch (SettingValueOutOfRangeException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
    }

    public function reset(string $key): void
    {
        $this->resetSettingAction->handle(SettingKeyEnum::from($key));
    }
}
