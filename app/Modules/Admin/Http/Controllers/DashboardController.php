<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController
{
    public function index(Request $request): View
    {
        $events = DB::table('adm_audit_logs')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'event', 'actor_id', 'actor_role', 'created_at']);

        return view('admin::dashboard', [
            'user' => $request->user(),
            'activeRole' => $request->user()?->activeRole(),
            'events' => $events,
        ]);
    }
}
