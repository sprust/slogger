<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Http\Controllers;

use App\Modules\Cleaner\Contracts\Actions\CreateSettingActionInterface;
use App\Modules\Cleaner\Contracts\Actions\DeleteSettingActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindSettingByIdActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindSettingsActionInterface;
use App\Modules\Cleaner\Contracts\Actions\UpdateSettingActionInterface;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\Cleaner\Infrastructure\Http\Requests\CreateSettingRequest;
use App\Modules\Cleaner\Infrastructure\Http\Requests\UpdateSettingRequest;
use App\Modules\Cleaner\Infrastructure\Http\Resources\SettingResource;
use App\Modules\Common\Helpers\ArrayValueGetter;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class SettingController
{
    public function __construct(
        private FindSettingByIdActionInterface $findSettingByIdAction,
        private FindSettingsActionInterface $findSettingsAction,
        private CreateSettingActionInterface $createSettingAction,
        private UpdateSettingActionInterface $updateSettingAction,
        private DeleteSettingActionInterface $deleteSettingAction
    ) {
    }

    #[OaListItemTypeAttribute(SettingResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return SettingResource::collection(
            $this->findSettingsAction->handle()
        );
    }

    public function store(CreateSettingRequest $request): SettingResource
    {
        $validated = $request->validated();

        try {
            $setting = $this->createSettingAction->handle(
                daysLifetime: ArrayValueGetter::int($validated, 'days_life_time'),
                type: ArrayValueGetter::stringNull($validated, 'type'),
                onlyData: ArrayValueGetter::bool($validated, 'only_data'),
            );
        } catch (SettingAlreadyExistsException $exception) {
            abort(Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }

        return new SettingResource($setting);
    }

    public function update(UpdateSettingRequest $request, int $settingId): SettingResource
    {
        $validated = $request->validated();

        try {
            $setting = $this->updateSettingAction->handle(
                settingId: $settingId,
                daysLifetime: ArrayValueGetter::int($validated, 'days_life_time'),
                onlyData: ArrayValueGetter::bool($validated, 'only_data'),
            );
        } catch (SettingNotFoundException|SettingAlreadyExistsException $exception) {
            abort(Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }

        return new SettingResource($setting);
    }

    public function destroy(int $settingId): void
    {
        abort_if(
            !$this->findSettingByIdAction->handle($settingId),
            Response::HTTP_NOT_FOUND
        );

        $this->deleteSettingAction->handle($settingId);
    }
}
