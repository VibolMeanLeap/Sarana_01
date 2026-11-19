<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_email_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('phone', 20);
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('otp_hash', 255);
            // DATETIME avoids legacy MySQL implicit TIMESTAMP defaults.
            $table->dateTime('otp_expires_at');
            $table->dateTime('draft_expires_at');
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->dateTime('last_sent_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();
            $table->unique('phone', 'customer_email_reg_phone_unique');
            $table->index(['draft_expires_at', 'consumed_at'], 'customer_email_reg_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_email_registrations');
    }
};
