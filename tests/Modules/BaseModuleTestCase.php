<?php

declare(strict_types=1);

namespace Tests\Modules;

use Tests\TestCase;

abstract class BaseModuleTestCase extends TestCase
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function appMake(string $class): object
    {
        return $this->app->make($class);
    }
}
