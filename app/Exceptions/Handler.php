<?php

declare (strict_types=1);

namespace App\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerRegistry;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /** @var array<int, string> */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $exception): bool {
            return $this->container->make(ExceptionManagerRegistry::class)->map($exception) === null;
        });

        $this->renderable(function (Throwable $exception, Request $request) {
            $mapped = $this->container->make(ExceptionManagerRegistry::class)->map($exception);

            return $mapped instanceof HttpException ? $this->render(
                request: $request,
                e: $mapped,
            ) : $mapped;
        });
    }
}
