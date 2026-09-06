<?php

namespace App\Providers;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Not the default `web` group: that one is session, cookies and CSRF, and the
        // panel authenticates with a bearer token. AuthMiddleware reads that token and
        // sets the user resolver, which is what SConcurBroadcaster::auth() reaches
        // through $request->user() when it runs the callbacks of routes/channels.php.
        Broadcast::routes([
            'middleware' => [
                AuthMiddleware::class,
            ],
        ]);

        require base_path('routes/channels.php');
    }
}
