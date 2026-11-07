<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('product_id')->constrained('product', 'product_id')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->string('type');
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};