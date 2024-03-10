<?php

namespace App\Modules\TraceCleaner\Http\Controllers;

use App\Modules\TraceCleaner\Exceptions\SettingAlreadyExistsException;
use App\Modules\TraceCleaner\Exceptions\SettingNotFoundException;
use App\Modules\TraceCleaner\Http\Requests\CreateSettingRequest;
use App\Modules\TraceCleaner\Http\Requests\UpdateSettingRequest;
use App\Modules\TraceCleaner\Http\Resources\SettingResource;
use App\Modules\TraceCleaner\Services\SettingService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class SettingController
{
    public function __construct(
        private SettingService $settingService
    ) {
    }

    #[OaListItemTypeAttribute(SettingResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return SettingResource::collection(
            $this->settingService->find()
        );
    }

    public function store(CreateSettingRequest $request): SettingResource
    {
        $validated = $request->validated();

        try {
            $setting = $this->settingService->create(
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
            $setting = $this->settingService->update(
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
            !$this->settingService->findOneById($settingId),
            Response::HTTP_NOT_FOUND
        );

        $this->settingService->delete($settingId);
    }
}
