<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\UseCases;

use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Domain\Actions\FindTraceLifetimeAction;

readonly class ClearTracesUseCase
{
    public function __construct(private ClearTracesAction $clearTracesAction, private FindTraceLifetimeAction $findTraceLifetimeAction)
    {
    }

    public function handle(): void
    {
        $this->clearTracesAction->handle($this->findTraceLifetimeAction->handle());
    }
}
