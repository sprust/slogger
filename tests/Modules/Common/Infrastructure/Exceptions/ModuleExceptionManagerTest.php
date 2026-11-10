<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Exceptions;

use App\Modules\Auth\Domain\Exceptions\AbstractAuthException;
use App\Modules\Auth\Infrastructure\Exceptions\AuthExceptionManager;
use App\Modules\Common\Domain\Exceptions\AbstractCommonException;
use App\Modules\Common\Infrastructure\Exceptions\CommonExceptionManager;
use App\Modules\Dashboard\Domain\Exceptions\AbstractDashboardException;
use App\Modules\Dashboard\Infrastructure\Exceptions\DashboardExceptionManager;
use App\Modules\Logs\Domain\Exceptions\AbstractLogsException;
use App\Modules\Logs\Infrastructure\Exceptions\LogsExceptionManager;
use App\Modules\Mcp\Domain\Exceptions\AbstractMcpException;
use App\Modules\Mcp\Infrastructure\Exceptions\McpExceptionManager;
use App\Modules\Notification\Domain\Exceptions\AbstractNotificationException;
use App\Modules\Notification\Infrastructure\Exceptions\NotificationExceptionManager;
use App\Modules\Service\Domain\Exceptions\AbstractServiceException;
use App\Modules\Service\Infrastructure\Exceptions\ServiceExceptionManager;
use App\Modules\Setting\Domain\Exceptions\AbstractSettingException;
use App\Modules\Setting\Infrastructure\Exceptions\SettingExceptionManager;
use App\Modules\Trace\Domain\Exceptions\AbstractTraceException;
use App\Modules\Trace\Infrastructure\Exceptions\TraceExceptionManager;
use App\Modules\User\Domain\Exceptions\AbstractUserException;
use App\Modules\User\Infrastructure\Exceptions\UserExceptionManager;
use App\Modules\Watcher\Domain\Exceptions\AbstractWatcherException;
use App\Modules\Watcher\Infrastructure\Exceptions\WatcherExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagerInterface;
use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagers;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Modules\BaseModuleTestCase;
use Throwable;

class ModuleExceptionManagerTest extends BaseModuleTestCase
{
    /** @return iterable<string, array{Throwable, class-string<ExceptionManagerInterface>, int, string}> */
    public static function modules(): iterable
    {
        yield 'Auth' => [
            new class('Authentication rejected') extends AbstractAuthException {
            },
            AuthExceptionManager::class,
            400,
            'Authentication rejected',
        ];

        yield 'Common' => [
            new class('Internal diagnostic detail') extends AbstractCommonException {
            },
            CommonExceptionManager::class,
            500,
            'Server Error',
        ];

        yield 'Dashboard' => [
            new class('Internal diagnostic detail') extends AbstractDashboardException {
            },
            DashboardExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Logs' => [
            new class('Internal diagnostic detail') extends AbstractLogsException {
            },
            LogsExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Mcp' => [
            new class('Internal diagnostic detail') extends AbstractMcpException {
            },
            McpExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Notification' => [
            new class('Internal diagnostic detail') extends AbstractNotificationException {
            },
            NotificationExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Service' => [
            new class('Internal diagnostic detail') extends AbstractServiceException {
            },
            ServiceExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Setting' => [
            new class('Internal diagnostic detail') extends AbstractSettingException {
            },
            SettingExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Trace' => [
            new class('Internal diagnostic detail') extends AbstractTraceException {
            },
            TraceExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'User' => [
            new class('Internal diagnostic detail') extends AbstractUserException {
            },
            UserExceptionManager::class,
            400,
            'Unknown error',
        ];

        yield 'Watcher' => [
            new class('Internal diagnostic detail') extends AbstractWatcherException {
            },
            WatcherExceptionManager::class,
            400,
            'Unknown error',
        ];
    }

    /** @param class-string<ExceptionManagerInterface> $managerClass */
    #[DataProvider('modules')]
    public function testNewModuleExceptionIsHandledAndReported(
        Throwable $exception,
        string $managerClass,
        int $status,
        string $message,
    ): void {
        $manager           = $this->appMake($managerClass);
        $exceptionManagers = $this->appMake(ExceptionManagers::class);
        $foreignException  = $exception instanceof AbstractAuthException
            ? new class extends AbstractCommonException {
            }
        : new class extends AbstractAuthException {
        };

        foreach ([$exception, new StreamedResponseException($exception)] as $sourceException) {
            self::assertTrue(
                $manager->isReportable($sourceException)
            );
            self::assertTrue(
                $exceptionManagers->isReportable($sourceException)
            );

            $response = $manager->makeJsonHttpResponse($sourceException);

            self::assertNotNull(
                $response
            );
            self::assertSame(
                expected: $status,
                actual: $response->httpStatus
            );
            self::assertSame(
                expected: $message,
                actual: $response->message
            );
            self::assertSame(
                expected: false,
                actual: $response->isError
            );
            self::assertEquals(
                expected: $response,
                actual: $exceptionManagers->makeJsonHttpResponse($sourceException)
            );
        }

        foreach ([$foreignException, new StreamedResponseException($foreignException)] as $sourceException) {
            self::assertNull(
                $manager->isReportable($sourceException)
            );
            self::assertNull(
                $manager->makeJsonHttpResponse($sourceException)
            );
        }
    }
}
