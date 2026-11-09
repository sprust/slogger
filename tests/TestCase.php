<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionClassConstant;
use Illuminate\Support\Facades\Http;
use SConcur\Context\Context;
use SConcur\Laravel\Config\AsyncConfig;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $fakeEvents = true;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        $this->forgetConfigOverlay();

        parent::tearDown();
    }

    private function forgetConfigOverlay(): void
    {
        foreach (
            [
                'CTX_KEY',
                'CTX_PATHS_KEY',
            ] as $constant
        ) {
            $reflectionClassConstant = new ReflectionClassConstant(
                class: AsyncConfig::class,
                constant: $constant,
            );

            Context::current()->forget((string) $reflectionClassConstant->getValue());
        }
    }
}
