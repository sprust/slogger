<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Entities\Cursor\LogCursorObject;
use App\Modules\Logs\Entities\Cursor\LogCursorPositionObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;

readonly class LogCursorCodec
{
    private const int VERSION = 1;

    public function encode(LogCursorObject $cursor): string
    {
        $files = [];

        foreach ($cursor->positions as $position) {
            $files[$position->fileId] = [$position->position, $position->headLength, $position->headHash];
        }

        $json = (string) json_encode([
            'v' => self::VERSION,
            'd' => $cursor->direction->value,
            'f' => (object) $files,
        ]);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * @param list<string> $allowedFileIds
     */
    public function decode(string $cursor, array $allowedFileIds): LogCursorObject
    {
        $json = base64_decode(strtr($cursor, '-_', '+/'), true);

        $data = $json === false ? null : json_decode($json, true);

        if (!is_array($data)) {
            throw new LogCursorInvalidException('not a cursor');
        }

        if (($data['v'] ?? null) !== self::VERSION) {
            throw new LogCursorInvalidException('unknown version');
        }

        $direction = LogCursorDirectionEnum::tryFrom(is_string($data['d'] ?? null) ? $data['d'] : '');

        if ($direction === null) {
            throw new LogCursorInvalidException('unknown direction');
        }

        if (!is_array($data['f'] ?? null)) {
            throw new LogCursorInvalidException('no files');
        }

        $positions = [];

        foreach ($data['f'] as $fileId => $value) {
            $fileId = (string) $fileId;

            if (!in_array($fileId, $allowedFileIds, true)) {
                throw new LogCursorInvalidException(sprintf('file [%s] is not asked for', $fileId));
            }

            if (
                !is_array($value)
                || count($value) !== 3
                || !is_int($value[0] ?? null)
                || $value[0] < -1
                || !is_int($value[1] ?? null)
                || $value[1] < 0
                || !is_string($value[2] ?? null)
                || preg_match('/^[0-9a-f]{32}$/', $value[2]) !== 1
            ) {
                throw new LogCursorInvalidException(sprintf('position of file [%s] is malformed', $fileId));
            }

            $positions[] = new LogCursorPositionObject(
                fileId: $fileId,
                position: $value[0],
                headLength: $value[1],
                headHash: $value[2]
            );
        }

        return new LogCursorObject(direction: $direction, positions: $positions);
    }
}
