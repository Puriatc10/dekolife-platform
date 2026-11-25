<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * API entry point. Module routes are registered by each module's provider under /api/v1
 * (see `app/Modules/Identity/routes/api.php`); this file only answers the root so a client
 * can discover the version it is talking to.
 */
Route::get('/', fn (): array => [
    'name' => (string) config('app.brand.en', 'Dekolife').' API',
    'version' => 'v1',
    'documentation' => '/docs/api/endpoint-inventory.md',
]);
