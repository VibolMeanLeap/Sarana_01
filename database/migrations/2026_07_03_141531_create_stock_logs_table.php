<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('product_id');

            $table->enum('type',['IN','OUT']);

            $table->integer('quantity');

            $table->string('note')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};