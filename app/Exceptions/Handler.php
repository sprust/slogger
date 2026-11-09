<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagers;
use App\Modules\Common\Infrastructure\Exceptions\ReportableException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->renderable(
            function (Throwable $exception, Request $request) {
                $exceptionManagers = $this->container->make(ExceptionManagers::class);
                $responseObject    = $exceptionManagers->makeJsonHttpResponse($exception);

                if ($responseObject === null) {
                    return null;
                }

                if (!$request->expectsJson() && $responseObject->messageField === 'message') {
                    return $this->render(
                        request: $request,
                        e: new HttpException(
                            statusCode: $responseObject->httpStatus,
                            message: $responseObject->message,
                            previous: $exception,
                        ),
                    );
                }

                $content = [
                    $responseObject->messageField => $responseObject->message,
                ];

                if ($responseObject->errorCode !== null) {
                    $content['error_code'] = $responseObject->errorCode;
                }

                return response()->json(
                    data: $content,
                    status: $responseObject->httpStatus,
                );
            }
        );
    }

    public function report(Throwable $e): void
    {
        if ($e instanceof ReportableException) {
            parent::report(
                $e->getException()
            );

            return;
        }

        if (!$this->container->make(ExceptionManagers::class)->isReportable($e)) {
            return;
        }

        parent::report($e);
    }
}
