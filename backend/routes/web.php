<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Medical Appointment API',
        'version' => '1.0.0',
    ]);
});


