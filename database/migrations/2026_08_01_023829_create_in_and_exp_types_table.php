<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_and_exp_types', function (Blueprint $table) {
            $table->id('id_type');
            $table->string('name');
            $table->timestamps();
        });

        \DB::table('in_and_exp_types')->insert([
            ['id_type' => 1, 'name' => 'Income', 'created_at' => now(), 'updated_at' => now()],
            ['id_type' => 2, 'name' => 'Expense', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('in_and_exp_types');
    }
};