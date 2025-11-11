<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RajaOngkirController;

Route::get('/provinces', [RajaOngkirController::class, 'getProvinces']);
Route::get('/get-kota/{province_id}', [RajaOngkirController::class, 'getCities']);
Route::post('/ongkir', [RajaOngkirController::class, 'checkOngkir']);