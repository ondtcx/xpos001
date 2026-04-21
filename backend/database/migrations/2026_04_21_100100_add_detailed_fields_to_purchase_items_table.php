<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('line_type')->default('normal')->after('variant_id');
            $table->decimal('bonus_quantity', 12, 3)->default(0)->after('quantity');
            $table->integer('tax_iva_amount')->default(0)->after('line_discount_amount');
            $table->integer('tax_ice_amount')->default(0)->after('tax_iva_amount');
            $table->integer('allocated_global_discount_amount')->default(0)->after('tax_other_amount');
            $table->integer('allocated_global_tax_iva_amount')->default(0)->after('allocated_global_discount_amount');
            $table->integer('allocated_global_tax_ice_amount')->default(0)->after('allocated_global_tax_iva_amount');
            $table->integer('allocated_global_tax_other_amount')->default(0)->after('allocated_global_tax_ice_amount');
            $table->integer('allocated_extra_costs_amount')->default(0)->after('allocated_global_tax_other_amount');
            $table->integer('unit_cost_final_amount')->default(0)->after('total_cost_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'line_type',
                'bonus_quantity',
                'tax_iva_amount',
                'tax_ice_amount',
                'allocated_global_discount_amount',
                'allocated_global_tax_iva_amount',
                'allocated_global_tax_ice_amount',
                'allocated_global_tax_other_amount',
                'allocated_extra_costs_amount',
                'unit_cost_final_amount',
            ]);
        });
    }
};
