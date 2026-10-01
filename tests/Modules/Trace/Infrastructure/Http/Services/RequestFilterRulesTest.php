<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Infrastructure\Http\Services;

use App\Modules\Trace\Infrastructure\Http\Services\RequestFilterRules;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequestFilterRulesTest extends TestCase
{
    public function testDataFieldWithATrailingLineBreakIsRefused(): void
    {
        $this->assertTrue($this->passes(['data' => ['filter' => [['field' => 'dt.abc', 'exists' => true]]]]));
        $this->assertFalse($this->passes(['data' => ['filter' => [['field' => "dt.abc\n", 'exists' => true]]]]));
    }

    public function testDataKeyWithATrailingLineBreakIsRefused(): void
    {
        $rules = ['key' => ['required', 'string', RequestFilterRules::DATA_KEY_RULE]];

        $this->assertTrue(Validator::make(['key' => 'abc'], $rules)->passes());
        $this->assertFalse(Validator::make(['key' => "abc\n"], $rules)->passes());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function hugeNumberProvider(): array
    {
        return [
            'duration from'        => [['duration_from' => '1e400']],
            'duration to'          => [['duration_to' => INF]],
            'memory from'          => [['memory_from' => '1e16']],
            'cpu to'               => [['cpu_to' => '-1']],
            'data number'          => [['data' => ['filter' => [['field' => 'dt.a', 'numeric' => ['value' => '1e400', 'comp' => '=']]]]]],
            'negative data number' => [['data' => ['filter' => [['field' => 'dt.a', 'numeric' => ['value' => '-1e21', 'comp' => '=']]]]]],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('hugeNumberProvider')]
    public function testHugeNumberIsRefused(array $data): void
    {
        $this->assertFalse($this->passes($data));
    }

    public function testOrdinaryNumbersPass(): void
    {
        $this->assertTrue(
            $this->passes([
                'duration_from' => '0.5',
                'cpu_to'        => 90,
                'data'          => ['filter' => [['field' => 'dt.a', 'numeric' => ['value' => -1.5e18, 'comp' => '>=']]]],
            ])
        );
    }

    public function testEmptyFacetValueIsRefused(): void
    {
        $this->assertFalse($this->passes(['tags' => ['']]));
        $this->assertFalse($this->passes(['types' => [str_repeat('x', 2001)]]));
        $this->assertTrue($this->passes(['statuses' => ['failed']]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function passes(array $data): bool
    {
        return Validator::make(
            $data,
            [
                ...RequestFilterRules::types(),
                ...RequestFilterRules::tags(),
                ...RequestFilterRules::statuses(),
                ...RequestFilterRules::data(),
                ...RequestFilterRules::durationFromTo(),
                ...RequestFilterRules::memoryFromTo(),
                ...RequestFilterRules::cpuFromTo(),
            ]
        )->passes();
    }
}
