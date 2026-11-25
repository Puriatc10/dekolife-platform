<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store and supplier profiles with an approval workflow (پیوست ۳ ۳-۱/۳-۴, پیوست ۶ بند ۱۰:
 * «ثبت اطلاعات و گزارش‌گیری ... فقط با تأیید ادمین»).
 *
 * A profile starts as `pending`, is reviewed by an admin, and only an approved profile
 * grants the role. The national id is never stored in clear: hash for duplicate detection,
 * encrypted copy for privileged display.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mer_merchant_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 16);                     // store | supplier
            $table->string('business_name', 160);
            $table->string('owner_name', 120)->nullable();
            $table->string('national_id_hash', 64);
            $table->text('national_id_encrypted');
            $table->string('postal_code', 10)->nullable();
            $table->string('province', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('address', 400)->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('status', 16)->default('pending'); // pending | approved | rejected
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // A user has at most one live profile per type; a rejected one can be replaced.
            $table->unique(['user_id', 'type']);
            $table->index(['status', 'created_at']);
            $table->index('national_id_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mer_merchant_profiles');
    }
};
