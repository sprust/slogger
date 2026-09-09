<?php

use App\Modules\Auth\Entities\LoggedUserObject;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Every channel here is private, and every callback is the same `true`. That is not
| laziness: there are no roles in this application (app/Models/Users/User.php) and traces
| are not split per user, so anyone signed in sees everything. What private buys is the
| step before that — a public channel is subscribed without asking the application at
| all, and the app key that would be enough to do it travels in the panel's bundle.
|
| The `sl-` prefix keeps our names apart from anyone else's on the bus, which is a shared
| fanout exchange rather than a private one.
|
*/

Broadcast::channel(
    'sl-trace-tree.{rootTraceId}',
    static fn(LoggedUserObject $user, string $rootTraceId): bool => true
);

Broadcast::channel('sl-trace-indexes', static fn(LoggedUserObject $user): bool => true);

Broadcast::channel(
    'sl-trace-index.{indexId}',
    static fn(LoggedUserObject $user, string $indexId): bool => true
);

/*
 * One channel for every watcher: the header's badge follows all of them at once, and a
 * channel per watcher would mean subscribing to a list that changes while the panel is
 * open.
 */
Broadcast::channel('sl-watchers', static fn(LoggedUserObject $user): bool => true);
