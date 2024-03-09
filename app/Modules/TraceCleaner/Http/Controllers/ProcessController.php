<?php

namespace App\Modules\TraceCleaner\Http\Controllers;

use App\Modules\TraceCleaner\Http\Resources\ProcessResource;
use App\Modules\TraceCleaner\Services\ProcessService;
use App\Modules\TraceCleaner\Services\SettingService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class ProcessController
{
    public function __construct(
        private SettingService $settingService,
        private ProcessService $processService
    ) {
    }

    #[OaListItemTypeAttribute(ProcessResource::class)]
    public function index(int $settingId): AnonymousResourceCollection
    {
        abort_if(
            !$this->settingService->findOneById($settingId),
            Response::HTTP_NOT_FOUND
        );

        return ProcessResource::collection(
            $this->processService->find(
                page: 1,
                settingId: $settingId
            )
        );
    }
}
