<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for sensitive events (پیوست ۱ بند ۱۰: login, price change, role change,
 * settlement). Rows are append-only: the application never updates or deletes them, and
 * `previous_hash`/`hash` chain them so a deleted row is detectable (docs/security).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->nullable();
            $table->string('event', 64);
            $table->string('subject_type', 96)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_audit_logs');
    }
};
