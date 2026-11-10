<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Domain\Actions\Mutations\CreateTraceAdminStoreAction;

readonly class CreateTraceAdminStoreByAdminUseCase
{
    public function __construct(
        private CreateTraceAdminStoreAction $createTraceAdminStoreAction
    ) {
    }

    public function handle(
        string $title,
        int $storeVersion,
        string $storeData,
        bool $auto
    ): TraceAdminStoreObject {
        return $this->createTraceAdminStoreAction->handle(
            title: $title,
            storeVersion: $storeVersion,
            storeData: $storeData,
            auto: $auto,
        );
    }
}
