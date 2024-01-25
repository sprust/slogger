<?php

namespace App\Providers;

use App\Listeners\RrJobsWorkerErrorListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandlingErrorEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        RrJobsPayloadHandlingErrorEvent::class => [
            RrJobsWorkerErrorListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
