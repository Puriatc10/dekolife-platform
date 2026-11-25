<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * National-id registry for duplicate detection (پیوست ۵ بند ۳: «جستجوی فروشگاه‌های ثبت‌شده
 * بر اساس سه شناسه کدملی، کدپستی و شماره موبایل برای جلوگیری از ثبت تکراری»).
 *
 * The registry stores a keyed hash for the uniqueness constraint and an encrypted copy for
 * privileged display, so a database dump alone cannot enumerate national ids (AMB-CTR-003).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usr_national_ids', function (Blueprint $table): void {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->text('encrypted');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('registration'); // registration | merchant_profile
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usr_national_ids');
    }
};
