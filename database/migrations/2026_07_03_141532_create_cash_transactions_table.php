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

            $table->enum('type',['Income','Expense']);

            $table->decimal('amount',10,2);

            $table->string('description')->nullable();

            $table->date('transaction_date');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};