<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TraceDataPathResolverTest extends TestCase
{
    public function testPathIsTakenAsItIs(): void
    {
        $path = $this->resolver()->resolve('dt.x');

        self::assertSame(['dt', 'x'], $path->segments);
        self::assertSame('dt.`dt`.`x`', $path->objectExpression);
        self::assertNull($path->arrayExpression);
        self::assertSame([], $path->arraySegments);
    }

    public function testFieldLosesOnlyItsOwnPrefix(): void
    {
        self::assertSame(['dt', 'x'], $this->resolver()->resolveField('dt.dt.x')->segments);
        self::assertSame(['dtx'], $this->resolver()->resolveField('dt.dtx')->segments);
        self::assertSame(['request', 'uri'], $this->resolver()->resolveField('dt.request.uri')->segments);
    }

    public function testFieldWithoutPrefixIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver()->resolveField('dtx.y');
    }

    public function testPathThroughAnArrayOfObjects(): void
    {
        $path = $this->resolver(['order.items'])->resolve('order.items.price');

        self::assertSame(['order', 'items'], $path->arraySegments);
        self::assertSame('dt.`order`.`items`[].`price`', $path->arrayExpression);
        self::assertSame('dt.`order`.`items`.`price`', $path->objectExpression);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPathProvider(): array
    {
        return [
            'trailing line break' => ["abc\n"],
            'quote'               => ["a'b"],
            'dash'                => ['a-b'],
            'empty segment'       => ['a..b'],
            'empty'               => [''],
        ];
    }

    #[DataProvider('invalidPathProvider')]
    public function testInvalidPathIsRefused(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver()->resolve($path);
    }

    /**
     * @param string[] $arrayPaths
     */
    private function resolver(array $arrayPaths = []): TraceDataPathResolver
    {
        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn($arrayPaths);

        return new TraceDataPathResolver($pathTypes);
    }
}
