<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionManagerRegistry
{
    /** @var array<class-string<ExceptionManagerInterface>, ExceptionManagerInterface> */
    private array $managers = [];

    public function register(ExceptionManagerInterface $exceptionManager): void
    {
        $this->managers[$exceptionManager::class] = $exceptionManager;
    }

    public function map(Throwable $exception): Response|HttpException|null
    {
        foreach ($this->managers as $exceptionManager) {
            $mapped = $exceptionManager->map($exception);

            if ($mapped !== null) {
                return $mapped;
            }
        }

        return null;
    }
}
