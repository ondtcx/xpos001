<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('original_unit_price_amount')->nullable()->after('unit_price_amount');
            $table->integer('manual_unit_price_amount')->nullable()->after('original_unit_price_amount');
            $table->boolean('has_manual_price_override')->default(false)->after('manual_unit_price_amount');
            $table->text('manual_price_reason')->nullable()->after('has_manual_price_override');
            $table->boolean('has_stock_warning')->default(false)->after('has_cost_warning');
            $table->boolean('stock_warning_acknowledged')->default(false)->after('has_stock_warning');
            $table->boolean('cost_warning_acknowledged')->default(false)->after('stock_warning_acknowledged');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'original_unit_price_amount',
                'manual_unit_price_amount',
                'has_manual_price_override',
                'manual_price_reason',
                'has_stock_warning',
                'stock_warning_acknowledged',
                'cost_warning_acknowledged',
            ]);
        });
    }
};
