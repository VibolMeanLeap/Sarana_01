<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_corrections')) {
            return;
        }

        Schema::create('order_corrections', function (Blueprint $table) {
            // Deliberately no foreign keys: this project supports legacy SQL imports.
            $table->bigIncrements('correction_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('reason', 60);
            $table->text('notes');
            $table->json('before_snapshot');
            $table->json('after_snapshot');
            $table->decimal('original_total', 12, 2);
            $table->decimal('corrected_total', 12, 2);
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->string('payment_adjustment_status', 20)->default('not_required');
            $table->string('payment_adjustment_type', 20)->nullable();
            $table->text('payment_adjustment_note')->nullable();
            $table->unsignedBigInteger('payment_adjusted_by')->nullable();
            $table->timestamp('payment_adjusted_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at'], 'order_corrections_order_created_idx');
            $table->index('payment_adjustment_status', 'order_corrections_adjustment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_corrections');
    }
};
