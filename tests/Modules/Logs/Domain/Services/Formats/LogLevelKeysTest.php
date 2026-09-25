<?php

namespace Tests\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Domain\Services\Formats\LogLevelKeys;
use App\Modules\Logs\Enums\LogTypeEnum;
use Tests\TestCase;

class LogLevelKeysTest extends TestCase
{
    public function testAKeyIsTheTypeAndTheLevelName(): void
    {
        $keys = $this->app->make(LogLevelKeys::class);

        $this->assertSame('laravel.ERROR', $keys->makeKey(LogTypeEnum::Laravel, 5));
        $this->assertSame('nginx_access.4xx', $keys->makeKey(LogTypeEnum::NginxAccess, 4));
        $this->assertSame('nginx_error.crit', $keys->makeKey(LogTypeEnum::NginxError, 6));
        $this->assertSame('laravel.none', $keys->makeKey(LogTypeEnum::Laravel, 0));
    }

    public function testKeysAreParsedByTypeAndUnknownOnesAreSkipped(): void
    {
        $keys = ['laravel.ERROR', 'laravel.none', 'nginx_access.5xx', 'laravel.TRACE', 'bogus.ERROR', 'nodot'];

        $levelKeys = $this->app->make(LogLevelKeys::class);

        $this->assertSame([5, 0], $levelKeys->parseKeys($keys, LogTypeEnum::Laravel));
        $this->assertSame([5], $levelKeys->parseKeys($keys, LogTypeEnum::NginxAccess));
        $this->assertSame([], $levelKeys->parseKeys($keys, LogTypeEnum::NginxError));
    }
}
