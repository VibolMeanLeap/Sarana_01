<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('order_id');

            $table->string('driver_name');

            $table->string('phone');

            $table->string('address');

            $table->enum('status',[
                'Pending',
                'Shipping',
                'Delivered'
            ])->default('Pending');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};