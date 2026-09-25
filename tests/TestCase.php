<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionClassConstant;
use SConcur\Context\Context;
use SConcur\Laravel\Config\AsyncConfig;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        $this->forgetConfigOverlay();

        parent::tearDown();
    }

    private function forgetConfigOverlay(): void
    {
        foreach (['CTX_KEY', 'CTX_PATHS_KEY'] as $constant) {
            Context::current()->forget(
                (string) new ReflectionClassConstant(AsyncConfig::class, $constant)->getValue()
            );
        }
    }
}
