<?php

use App\Http\Controllers\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('imports', ImportController::class)->only(['index','store','show']);

