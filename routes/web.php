<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RajaOngkirController;

Route::get('/', [RajaOngkirController::class, 'index'])->name('ongkir.index');