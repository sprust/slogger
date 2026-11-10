<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions;

use App\Modules\Setting\Domain\Actions\FindSettingValueAction;
use App\Modules\Setting\Enums\SettingKeyEnum;

readonly class FindMcpTraceLifetimeAction
{
    public function __construct(
        private FindSettingValueAction $findSettingValueAction
    ) {
    }

    public function handle(): int
    {
        return $this->findSettingValueAction->handle(SettingKeyEnum::TracesLifetimeHours);
    }
}
