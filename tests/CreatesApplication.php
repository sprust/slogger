<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\Facades\Event;
use Tests\Support\TestingEnvironment;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        TestingEnvironment::load();

        $application = require __DIR__ . '/../bootstrap/app.php';

        TestingEnvironment::guard($application);

        if ($this->fakeEvents) {
            // Module providers resolve the singleton dispatcher while registering jobs.
            $application->beforeBootstrapping(
                bootstrapper: BootProviders::class,
                callback: static function (): void {
                    Event::fake();
                },
            );
        }

        $application->make(Kernel::class)->bootstrap();

        return $application;
    }
}
