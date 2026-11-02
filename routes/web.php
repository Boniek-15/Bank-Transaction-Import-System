<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/imports');

Route::inertia('imports', 'Imports')->name('Imports');


