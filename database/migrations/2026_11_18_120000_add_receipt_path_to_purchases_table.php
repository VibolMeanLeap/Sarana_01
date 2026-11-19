<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchases', 'receipt_path')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('receipt_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchases', 'receipt_path')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('receipt_path');
            });
        }
    }
};
