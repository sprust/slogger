<?php

namespace App\Providers;

use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Infrastructure\Listeners\DispatchNotificationListener;
use App\Modules\Notification\Infrastructure\Listeners\EnqueueNotificationsListener;
use App\Modules\Trace\Domain\Events\TraceDynamicIndexBuiltEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Infrastructure\Listeners\BroadcastTraceDynamicIndexBuiltListener;
use App\Modules\Trace\Infrastructure\Listeners\BroadcastTraceTreeStateListener;
use App\Modules\Trace\Infrastructure\Listeners\DispatchTraceTreeCacheBuildListener;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Infrastructure\Listeners\BroadcastWatcherIncidentListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TraceTreeCacheBuildRequestedEvent::class => [
            DispatchTraceTreeCacheBuildListener::class,
        ],
        TraceTreeCacheStateChangedEvent::class   => [
            BroadcastTraceTreeStateListener::class,
        ],
        TraceDynamicIndexBuiltEvent::class       => [
            BroadcastTraceDynamicIndexBuiltListener::class,
        ],
        NotificationEnqueuedEvent::class         => [
            DispatchNotificationListener::class,
        ],
        WatcherIncidentChangedEvent::class       => [
            BroadcastWatcherIncidentListener::class,
            EnqueueNotificationsListener::class,
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
