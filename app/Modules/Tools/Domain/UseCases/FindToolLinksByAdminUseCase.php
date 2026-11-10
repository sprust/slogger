<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\UseCases;

use App\Modules\Tools\Domain\Actions\FindToolLinksAction;
use App\Modules\Tools\Entities\ToolLinksObject;

readonly class FindToolLinksByAdminUseCase
{
    public function __construct(private FindToolLinksAction $findToolLinksAction)
    {
    }

    /** @return ToolLinksObject[] */
    public function handle(): array
    {
        return $this->findToolLinksAction->handle();
    }
}
