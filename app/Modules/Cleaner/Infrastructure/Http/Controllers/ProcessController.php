<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Http\Controllers;

use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;
use App\Modules\Cleaner\Infrastructure\Http\Resources\ProcessResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ProcessController
{
    public function __construct(
        private FindProcessesAction $findProcessesAction
    ) {
    }

    #[OaListItemTypeAttribute(ProcessResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ProcessResource::collection(
            $this->findProcessesAction->handle(
                page: 1,
            )
        );
    }
}
