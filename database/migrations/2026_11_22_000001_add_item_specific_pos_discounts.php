<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'discount_amount')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->decimal('discount_amount', 10, 2)->nullable()->after('discount');
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'discount_percent')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->decimal('discount_percent', 5, 2)->nullable()->after('price');
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'discount_amount')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_percent');
            });
        }

        // The legacy discount column remains a percentage for existing triggers.
        // Four decimals let the app store an exact effective rate for selective
        // line discounts without losing cents on larger tickets.
        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('orders', 'discount')) {
            DB::statement('ALTER TABLE `orders` MODIFY `discount` DECIMAL(10,6) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        // Do not remove recorded monetary discounts automatically during rollback.
        // Keep the additive columns so historic receipts remain interpretable.
    }
};
