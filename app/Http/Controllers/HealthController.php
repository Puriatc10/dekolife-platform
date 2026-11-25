<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness/readiness endpoint.
 *
 * Returns 200 when the application can serve traffic. The database probe reports
 * readiness as data (`database: false`) instead of failing the whole request, so a
 * load balancer can distinguish "process up" from "dependency broken".
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = true;
        $databaseError = null;

        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            $database = false;
            $databaseError = config('app.debug') ? $e->getMessage() : 'unavailable';
        }

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'database' => $database,
            'database_error' => $databaseError,
            'time' => now()->toIso8601String(),
        ], 200, ['Cache-Control' => 'no-store']);
    }
}
