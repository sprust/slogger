<?php

namespace Tests\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Infrastructure\Protocol\McpStringTruncator;
use PHPUnit\Framework\TestCase;

class McpStringTruncatorTest extends TestCase
{
    public function testTruncatesNestedStrings(): void
    {
        $result = new McpStringTruncator()->truncate(
            ['a' => 'короткий', 'b' => ['c' => str_repeat('я', 12)], 'd' => 5],
            10
        );

        $this->assertSame('короткий', $result['a']);
        $this->assertSame(str_repeat('я', 10) . '…[truncated, 12 chars total]', $result['b']['c']);
        $this->assertSame(5, $result['d']);
    }
}
