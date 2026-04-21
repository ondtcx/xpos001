<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->integer('global_tax_iva_amount')->default(0)->after('global_discount_amount');
            $table->integer('global_tax_ice_amount')->default(0)->after('global_tax_iva_amount');
            $table->integer('global_tax_other_amount')->default(0)->after('global_tax_ice_amount');

            $table->unique(['supplier_id', 'invoice_number'], 'purchases_supplier_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_supplier_invoice_unique');
            $table->dropColumn([
                'global_tax_iva_amount',
                'global_tax_ice_amount',
                'global_tax_other_amount',
            ]);
        });
    }
};
