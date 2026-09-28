<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Entities\McpSettingsObject;

readonly class McpResultFactory
{
    public const string META_SERVER_INFO = 'io.modelcontextprotocol/serverInfo';

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    public function complete(McpSettingsObject $settings, array $result): array
    {
        return [
            'resultType' => 'complete',
            ...$result,
            '_meta'      => [
                self::META_SERVER_INFO => [
                    'name'    => "slogger-$settings->serverName",
                    'title'   => "SLogger ($settings->serverName)",
                    'version' => $settings->serverVersion,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    public function cacheable(McpSettingsObject $settings, array $result): array
    {
        return $this->complete(
            $settings,
            [
                ...$result,
                'ttlMs'      => $settings->listTtlMs,
                'cacheScope' => 'private',
            ]
        );
    }
}
