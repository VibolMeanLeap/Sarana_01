<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_google_login_otps')) {
            return;
        }

        Schema::create('admin_google_login_otps', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Match the legacy users.user_id key without adding a foreign key.
            $table->bigInteger('user_id')->index();
            $table->char('challenge_hash', 64)->unique();
            $table->string('code_hash', 255);

            // DATETIME avoids MySQL's legacy implicit TIMESTAMP default behavior.
            // The application explicitly supplies all three values on insert/update.
            $table->dateTime('otp_expires_at');
            $table->dateTime('challenge_expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->dateTime('last_sent_at');
            $table->dateTime('verified_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'verified_at'], 'admin_google_otp_user_verified_idx');
            $table->index('challenge_expires_at', 'admin_google_otp_challenge_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_google_login_otps');
    }
};
