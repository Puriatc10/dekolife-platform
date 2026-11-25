<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes the append-only audit trail (پیوست ۱ بند ۱۰).
 *
 * Every sensitive event goes through here: login, logout, role grant/revoke, active-role
 * switch, profile identity change, price change, settlement. Each row carries a hash of
 * (previous hash + payload) so a deleted or edited row breaks the chain and a nightly
 * verification job can prove it.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function log(string $event, array $changes = [], ?Model $subject = null): void
    {
        $actor = Auth::user();
        $role = $this->activeRoleCode($actor);

        DB::transaction(function () use ($event, $changes, $subject, $actor, $role): void {
            $previous = DB::table('adm_audit_logs')->orderByDesc('id')->value('hash');

            $payload = [
                'event' => $event,
                'actor_id' => $actor?->getAuthIdentifier(),
                'actor_role' => $role,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'changes' => $changes,
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
                'at' => now()->toIso8601String(),
            ];

            DB::table('adm_audit_logs')->insert([
                'actor_id' => $payload['actor_id'],
                'actor_role' => $payload['actor_role'],
                'event' => $payload['event'],
                'subject_type' => $payload['subject_type'],
                'subject_id' => $payload['subject_id'],
                'changes' => $changes === [] ? null : json_encode($changes, JSON_UNESCAPED_UNICODE),
                'ip' => $payload['ip'],
                'user_agent' => $payload['user_agent'],
                'previous_hash' => $previous,
                'hash' => hash('sha256', ($previous ?? '').json_encode($payload, JSON_UNESCAPED_UNICODE)),
                'created_at' => now(),
            ]);
        });
    }

    private function activeRoleCode(?Authenticatable $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        if (method_exists($actor, 'activeRoleCode')) {
            return $actor->activeRoleCode()?->value;
        }

        return null;
    }
}
