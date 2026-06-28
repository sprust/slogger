<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SConcur\Laravel\Http\Test\SconcurTestController;

// Bare routes (no auth/session middleware) so they can be hammered directly.
// Exposed only when config('sconcur.test_routes') is on.
Route::get('sconcur-test/isolation', [SconcurTestController::class, 'isolation']);
Route::get('sconcur-test/mongo', [SconcurTestController::class, 'mongo']);
Route::get('sconcur-test/pdo-tx', [SconcurTestController::class, 'pdoTx']);
Route::get('sconcur-test/pdo-tx-await', [SconcurTestController::class, 'pdoTxAwait']);
