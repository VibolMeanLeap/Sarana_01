<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! Schema::hasColumn('settings', 'delivery_fee')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->decimal('delivery_fee', 10, 2)->nullable()->default(null)->after('exchange_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'delivery_fee')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->dropColumn('delivery_fee');
            });
        }
    }
};
