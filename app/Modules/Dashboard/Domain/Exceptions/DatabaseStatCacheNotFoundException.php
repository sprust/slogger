<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Exceptions;

class DatabaseStatCacheNotFoundException extends AbstractDashboardException
{
    public function __construct()
    {
        parent::__construct('Dashboard database stat cache not found.');
    }
}
