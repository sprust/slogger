<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerRegistry;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Throwable;
use Tests\TestCase;

class ExceptionManagerTest extends TestCase
{
    /** @return iterable<string, array{Throwable, ?int, string, string}> */
    public static function cases(): iterable
    {
        yield 'requested log file' => [new RequestedLogFileNotFoundException('missing'), 404, 'message', 'Log file [missing] not found'];

        yield 'log indexing' => [new LogFileIndexingException('busy'), 409, 'message', 'busy'];

        yield 'watcher' => [new WatcherNotFoundException(7), 404, 'message', 'Watcher [7] not found'];

        yield 'unauthorized' => [new AuthenticationFailedException(), 401, 'message', ''];

        yield 'dashboard cache' => [new DatabaseStatCacheNotFoundException(), 503, 'error', 'No data yet. Please wait for the first cache refresh.'];

        yield 'internal disappearing file' => [new LogFileNotFoundException('/storage/logs/gone.log'), null, '', ''];

        yield 'unknown failure' => [new RuntimeException('failure'), null, '', ''];
    }

    #[DataProvider('cases')]
    public function test(Throwable $exception, ?int $status, string $field, string $message): void
    {
        $registry = $this->app->make(ExceptionManagerRegistry::class);

        if ($status === null) {
            $this->assertNull($registry->map($exception));

            $this->assertTrue($this->app->make(ExceptionHandler::class)->shouldReport($exception));

            return;
        }

        $request = Request::create('/admin-api/test');

        $request->headers->set('Accept', 'application/json');

        $response = $this->app->make(ExceptionHandler::class)->render($request, $exception);

        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame(
            $message,
            json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)[$field]
        );
    }
}
