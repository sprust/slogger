<?php

use App\Modules\Trace\Infrastructure\Http\Controllers\TraceCollectorController;
use Illuminate\Support\Facades\Route;

Route::post('', [TraceCollectorController::class, 'create'])->name('create');
Route::patch('', [TraceCollectorController::class, 'update'])->name('create');
