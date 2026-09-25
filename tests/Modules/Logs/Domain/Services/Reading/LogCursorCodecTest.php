<?php

namespace Tests\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Domain\Services\Reading\LogCursorCodec;
use App\Modules\Logs\Entities\Cursor\LogCursorObject;
use App\Modules\Logs\Entities\Cursor\LogCursorPositionObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LogCursorCodecTest extends TestCase
{
    public function testACursorSurvivesTheRoundTrip(): void
    {
        $cursor = new LogCursorObject(
            direction: LogCursorDirectionEnum::Newer,
            positions: [
                new LogCursorPositionObject(fileId: sha1('a'), position: -1, headLength: 0, headHash: md5('')),
                new LogCursorPositionObject(fileId: sha1('b'), position: 5_000_000, headLength: 1024, headHash: md5('head')),
            ]
        );

        $codec = new LogCursorCodec();

        $encoded = $codec->encode($cursor);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encoded);
        $this->assertEquals($cursor, $codec->decode($encoded, [sha1('a'), sha1('b'), sha1('c')]));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedCursors(): array
    {
        $file = sha1('a');
        $hash = md5('');

        return [
            'not base64'         => ['%%%'],
            'not json'           => [self::encode('nope')],
            'no version'         => [self::encode(json_encode(['d' => 'older', 'f' => []]))],
            'another version'    => [self::encode(json_encode(['v' => 2, 'd' => 'older', 'f' => []]))],
            'unknown direction'  => [self::encode(json_encode(['v' => 1, 'd' => 'up', 'f' => []]))],
            'no files'           => [self::encode(json_encode(['v' => 1, 'd' => 'older']))],
            'file not asked for' => [self::encode(json_encode(['v' => 1, 'd' => 'older', 'f' => [sha1('x') => [0, 0, $hash]]]))],
            'position too low'   => [self::encode(json_encode(['v' => 1, 'd' => 'older', 'f' => [$file => [-2, 0, $hash]]]))],
            'position a string'  => [self::encode(json_encode(['v' => 1, 'd' => 'older', 'f' => [$file => ['1', 0, $hash]]]))],
            'bad hash'           => [self::encode(json_encode(['v' => 1, 'd' => 'older', 'f' => [$file => [1, 0, 'zz']]]))],
            'missing part'       => [self::encode(json_encode(['v' => 1, 'd' => 'older', 'f' => [$file => [1, 0]]]))],
        ];
    }

    #[DataProvider('malformedCursors')]
    public function testAMalformedCursorIsRefused(string $cursor): void
    {
        $this->expectException(LogCursorInvalidException::class);

        new LogCursorCodec()->decode($cursor, [sha1('a')]);
    }

    private static function encode(string|false $json): string
    {
        return rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
    }
}
