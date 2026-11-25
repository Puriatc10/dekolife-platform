<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity module tables.
 *
 * Roles are data, not an enum column on users: the contract allows one secondary role
 * per user (پیوست ۷ بند ۶) and requires simultaneous roles for some pairs (پیوست ۱۲ بند ۱۱),
 * which a single `role` column cannot express (AMB-IAM-001, DEC-011).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iam_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label_fa', 64);
            $table->string('panel_path', 64);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('iam_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 96)->unique();
            $table->string('group', 48);
            $table->string('label_fa', 96);
            $table->timestamps();
        });

        Schema::create('iam_permission_role', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('iam_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('iam_permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('iam_user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('iam_roles')->restrictOnDelete();
            $table->foreignId('granted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // One live grant per user per role; revocation is recorded, never deleted.
            $table->unique(['user_id', 'role_id']);
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iam_user_roles');
        Schema::dropIfExists('iam_permission_role');
        Schema::dropIfExists('iam_permissions');
        Schema::dropIfExists('iam_roles');
    }
};
