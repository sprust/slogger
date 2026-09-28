<?php

use App\Modules\Mcp\Infrastructure\Http\Controllers\McpEndpointController;
use Illuminate\Support\Facades\Route;

Route::post('/mcp', [McpEndpointController::class, 'handle'])->name('mcp');
