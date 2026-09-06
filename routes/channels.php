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
