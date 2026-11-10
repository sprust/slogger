<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions;

use Exception;
use Throwable;

class ReportableException extends Exception
{
    private function __construct(private readonly Throwable $exception)
    {
        parent::__construct('Reportable exception', 0, $exception);
    }

    public static function create(Throwable $exception): ReportableException
    {
        return new ReportableException($exception);
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
