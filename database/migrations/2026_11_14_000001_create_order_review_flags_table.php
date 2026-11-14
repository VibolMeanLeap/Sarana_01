<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_review_flags')) {
            return;
        }

        Schema::create('order_review_flags', function (Blueprint $table) {
            $table->bigIncrements('flag_id');
            // No foreign keys: this app supports legacy databases imported from SQL dumps.
            $table->bigInteger('order_id');
            $table->bigInteger('related_order_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->string('reason', 50);
            $table->text('notes');
            $table->string('status', 20)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status'], 'order_review_flags_order_status_idx');
            $table->index('related_order_id', 'order_review_flags_related_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_review_flags');
    }
};
