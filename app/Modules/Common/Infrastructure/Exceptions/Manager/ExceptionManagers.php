<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions\Manager;

use Throwable;

class ExceptionManagers
{
    /**
     * @var ExceptionManagerInterface[]
     */
    private array $managers = [];

    public function register(ExceptionManagerInterface $manager): void
    {
        $this->managers[] = $manager;
    }

    public function isReportable(Throwable $exception): bool
    {
        foreach ($this->managers as $renderer) {
            $isReportable = $renderer->isReportable($exception);

            if (!is_null($isReportable)) {
                return $isReportable;
            }
        }

        return true;
    }

    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        foreach ($this->managers as $renderer) {
            if ($response = $renderer->makeJsonHttpResponse($exception)) {
                return $response;
            }
        }

        return null;
    }
}
