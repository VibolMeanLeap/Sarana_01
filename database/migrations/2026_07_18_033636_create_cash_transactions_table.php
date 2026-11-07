<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('accounting_category', 'id')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->dateTime('transaction_date');
            $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases', 'purchase_id')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};