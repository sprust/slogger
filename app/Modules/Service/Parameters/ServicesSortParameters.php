<?php

declare(strict_types=1);

namespace App\Modules\Service\Parameters;

readonly class ServicesSortParameters
{
    /**
     * @param int[] $ids
     */
    public function __construct(public array $ids)
    {
    }
}
