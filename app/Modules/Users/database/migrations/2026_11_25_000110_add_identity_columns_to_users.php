<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity columns the contract requires on a user: a verified mobile (پیوست ۲ بند ۸),
 * a national id used only for duplicate detection (پیوست ۵ بند ۳), forced password
 * change at first login (پیوست ۶ بند ۸) and an admin TOTP secret (پیوست ۱ بند ۷).
 *
 * The national id is stored as a salted hash for lookup plus a reversible copy for
 * display to privileged roles (AMB-CTR-003); the hash is what uniqueness is enforced on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('mobile', 16)->nullable()->unique()->after('email');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');
            $table->string('national_id_hash', 64)->nullable()->unique()->after('mobile_verified_at');
            $table->text('national_id_encrypted')->nullable()->after('national_id_hash');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->text('two_factor_secret')->nullable()->after('must_change_password');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_confirmed_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->boolean('is_active')->default(true)->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'mobile',
                'mobile_verified_at',
                'national_id_hash',
                'national_id_encrypted',
                'must_change_password',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'last_login_at',
                'last_login_ip',
                'is_active',
            ]);
        });
    }
};
