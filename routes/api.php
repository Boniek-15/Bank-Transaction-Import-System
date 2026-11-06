<?php

use App\Http\Controllers\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('imports', ImportController::class)->only(['index','store','show']);
Route::post('/imports/init', [ImportController::class, 'init']);
Route::post('/imports/chunk', [ImportController::class, 'chunk']);
Route::post('/imports/complete', [ImportController::class, 'complete']);

