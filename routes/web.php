<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/imports');

Route::inertia('imports', 'Imports')->name('Imports');

require __DIR__.'/settings.php';
