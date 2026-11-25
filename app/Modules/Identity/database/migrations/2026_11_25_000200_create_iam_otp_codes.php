<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time codes for registration, password reset and mobile change (REQ-IAM-001,
 * REQ-IAM-003).
 *
 * The code is stored as a hash, never in clear text: an operator with database access must
 * not be able to read a customer's live code. `payload` carries what the code authorises
 * (for a mobile change, the new number), so verification and effect are one atomic step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iam_otp_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('mobile', 16);
            $table->string('purpose', 32);           // register | reset_password | mobile_change
            $table->string('code_hash', 64);
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('requested_ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['mobile', 'purpose', 'consumed_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iam_otp_codes');
    }
};
