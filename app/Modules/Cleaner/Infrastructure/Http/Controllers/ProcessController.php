<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Http\Controllers;

use App\Modules\Cleaner\Contracts\Actions\FindProcessesActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindSettingByIdActionInterface;
use App\Modules\Cleaner\Infrastructure\Http\Resources\ProcessResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class ProcessController
{
    public function __construct(
        private FindSettingByIdActionInterface $findSettingByIdAction,
        private FindProcessesActionInterface $findProcessesAction
    ) {
    }

    #[OaListItemTypeAttribute(ProcessResource::class)]
    public function index(int $settingId): AnonymousResourceCollection
    {
        abort_if(
            !$this->findSettingByIdAction->handle($settingId),
            Response::HTTP_NOT_FOUND
        );

        return ProcessResource::collection(
            $this->findProcessesAction->handle(
                page: 1,
                settingId: $settingId
            )
        );
    }
}
