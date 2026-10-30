<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

Route::inertia('imports', 'Imports')->name('Imports');

require __DIR__.'/settings.php';
