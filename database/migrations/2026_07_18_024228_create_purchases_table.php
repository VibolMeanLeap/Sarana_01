<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id('purchase_id');

            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('product', 'product_id')
                ->restrictOnDelete();

            $table->integer('quantity');

            $table->decimal('cost_price', 12, 2);

            // Purchase Date
            $table->date('purchase_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};