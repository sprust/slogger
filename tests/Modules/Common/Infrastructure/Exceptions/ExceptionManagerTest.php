<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\Manager\ExceptionManagers;
use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Throwable;
use Tests\Modules\BaseModuleTestCase;

class ExceptionManagerTest extends BaseModuleTestCase
{
    /** @return iterable<string, array{Throwable, ?int, string, string, bool, bool}> */
    public static function cases(): iterable
    {
        $cases = [
            'requested log file' => [
                new RequestedLogFileNotFoundException('missing'),
                404,
                'message',
                'Log file [missing] not found',
            ],
            'log indexing'    => [new LogFileIndexingException('busy'), 409, 'message', 'busy'],
            'watcher'         => [new WatcherNotFoundException(7), 404, 'message', 'Watcher [7] not found'],
            'unauthorized'    => [new AuthenticationFailedException(), 401, 'message', 'Authentication failed'],
            'dashboard cache' => [
                new DatabaseStatCacheNotFoundException(),
                503,
                'error',
                'No data yet. Please wait for the first cache refresh.',
            ],
            'internal disappearing file' => [
                new LogFileNotFoundException('/storage/logs/gone.log'),
                500,
                'message',
                'Server Error',
            ],
            'invalid MCP server name' => [
                new McpServerNameInvalidException('private.invalid'),
                500,
                'message',
                'Server Error',
            ],
            'mutex timeout' => [
                new MutexLockTimeoutException('private-key'),
                500,
                'message',
                'Server Error',
            ],
            'unknown failure'            => [new RuntimeException('failure'), null, '', ''],
        ];

        foreach ($cases as $name => $case) {
            yield $name => [
                ...$case,
                $case[1] === null || $case[1] === 500,
                false,
            ];

            yield "$name inside streamed response" => [
                ...$case,
                $case[1] === null || $case[1] === 500,
                true,
            ];
        }
    }

    public function testDashboardResponseRemainsJsonWithoutAcceptHeader(): void
    {
        $request  = Request::create('/admin-api/dashboard');
        $response = $this->appMake(ExceptionHandler::class)->render(
            request: $request,
            e: new DatabaseStatCacheNotFoundException(),
        );

        self::assertSame(
            expected: 503,
            actual: $response->getStatusCode()
        );
        self::assertSame(
            expected: 'application/json',
            actual: $response->headers->get('Content-Type')
        );
        self::assertSame(
            expected: [
                'error' => 'No data yet. Please wait for the first cache refresh.',
            ],
            actual: json_decode(
                json: ($response->getContent() ?: throw new RuntimeException('Missing JSON content')),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            )
        );
    }

    #[DataProvider('cases')]
    public function test(
        Throwable $exception,
        ?int $status,
        string $field,
        string $message,
        bool $reportable,
        bool $streamed,
    ): void {
        $exceptionManagers = $this->appMake(ExceptionManagers::class);
        $sourceException   = $streamed ? new StreamedResponseException($exception) : $exception;
        $responseObject    = $exceptionManagers->makeJsonHttpResponse($sourceException);

        self::assertSame(
            expected: $reportable,
            actual: $exceptionManagers->isReportable($sourceException)
        );

        if ($status === null) {
            self::assertNull(
                $responseObject
            );

            return;
        }

        self::assertNotNull(
            $responseObject
        );
        self::assertSame(
            expected: $status,
            actual: $responseObject->httpStatus
        );
        self::assertSame(
            expected: $field,
            actual: $responseObject->messageField
        );
        self::assertSame(
            expected: $message,
            actual: $responseObject->message
        );

        $request = Request::create('/admin-api/test');
        $request->headers->set(
            key: 'Accept',
            values: 'application/json',
        );
        $response = $this->appMake(ExceptionHandler::class)->render(
            request: $request,
            e: $exception,
        );

        self::assertSame(
            expected: $status,
            actual: $response->getStatusCode()
        );
        self::assertSame(
            expected: $message,
            actual: json_decode(
                json: ($response->getContent() ?: throw new RuntimeException('Missing JSON content')),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            )[$field]
        );
    }
}
