<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

readonly class McpStringTruncator
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public function truncate(array $data, int $maxLength): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->truncate($value, $maxLength);
            } elseif (is_string($value) && mb_strlen($value) > $maxLength) {
                $data[$key] = sprintf(
                    '%s…[truncated, %d chars total]',
                    mb_substr($value, 0, $maxLength),
                    mb_strlen($value)
                );
            }
        }

        return $data;
    }
}
