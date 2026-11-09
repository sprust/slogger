<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

interface ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null;
}
