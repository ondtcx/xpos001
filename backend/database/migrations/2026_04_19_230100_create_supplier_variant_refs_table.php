<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_variant_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('supplier_product_name')->nullable();
            $table->string('supplier_code')->nullable();
            $table->integer('last_purchase_price_amount')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_variant_refs');
    }
};
