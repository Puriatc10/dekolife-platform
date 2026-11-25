<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * Web routes.
 *
 * Module routes are registered by each module's service provider (see
 * `app/Modules/<Module>/routes/web.php`) and are grouped here by area as the phases
 * land. Only framework-level and public entry points live in this file.
 */

Route::get('/health', HealthController::class)->name('health');

Route::get('/', function () {
    return view('welcome');
})->name('home');
