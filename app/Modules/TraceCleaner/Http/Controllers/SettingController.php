<?php

namespace App\Modules\TraceCleaner\Http\Controllers;

use App\Modules\TraceCleaner\Http\Requests\CreateSettingRequest;
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

    public function storeOrUpdate(CreateSettingRequest $request): SettingResource
    {
        $validated = $request->validated();

        $setting = $this->settingService->createOrUpdate(
            daysLifetime: $validated['days_life_time'],
            type: $validated['type'],
        );

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
