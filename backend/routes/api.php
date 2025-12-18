<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}/availability', [DoctorController::class, 'availability']);
Route::post('/appointments', [AppointmentController::class, 'store']);


