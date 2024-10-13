<?php

use App\Modules\Trace\Infrastructure\Http\Controllers\TraceCreateController;
use App\Modules\Trace\Infrastructure\Http\Controllers\TraceUpdateController;
use Illuminate\Support\Facades\Route;

Route::post('', TraceCreateController::class)->name('create');
Route::patch('', TraceUpdateController::class)->name('update');
