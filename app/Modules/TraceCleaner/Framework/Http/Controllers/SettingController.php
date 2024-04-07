<?php

namespace App\Modules\TraceCleaner\Framework\Http\Controllers;

use App\Modules\TraceCleaner\Domain\Actions\CreateSettingAction;
use App\Modules\TraceCleaner\Domain\Actions\DeleteSettingAction;
use App\Modules\TraceCleaner\Domain\Actions\FindSettingByIdAction;
use App\Modules\TraceCleaner\Domain\Actions\FindSettingsAction;
use App\Modules\TraceCleaner\Domain\Actions\UpdateSettingAction;
use App\Modules\TraceCleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\TraceCleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\TraceCleaner\Framework\Http\Requests\CreateSettingRequest;
use App\Modules\TraceCleaner\Framework\Http\Requests\UpdateSettingRequest;
use App\Modules\TraceCleaner\Framework\Http\Resources\SettingResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class SettingController
{
    public function __construct(
        private FindSettingByIdAction $findSettingByIdAction,
        private FindSettingsAction $findSettingsAction,
        private CreateSettingAction $createSettingAction,
        private UpdateSettingAction $updateSettingAction,
        private DeleteSettingAction $deleteSettingAction
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
                daysLifetime: $validated['days_life_time'],
                type: $validated['type'],
            );
        } catch (SettingAlreadyExistsException $exception) {
            abort(Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }

        return new SettingResource($setting);
    }

    public function update(int $settingId, UpdateSettingRequest $request): SettingResource
    {
        $validated = $request->validated();

        try {
            $setting = $this->updateSettingAction->handle(
                settingId: $settingId,
                daysLifetime: $validated['days_life_time'],
                type: $validated['type'],
            );
        } catch (SettingNotFoundException $exception) {
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
