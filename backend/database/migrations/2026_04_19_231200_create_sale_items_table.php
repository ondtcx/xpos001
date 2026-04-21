<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('item_type')->default('product');
            $table->foreignId('sale_presentation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('description_snapshot');
            $table->decimal('quantity', 12, 3);
            $table->integer('unit_price_amount');
            $table->integer('subtotal_amount');
            $table->integer('total_cost_amount')->nullable();
            $table->integer('total_profit_amount')->nullable();
            $table->boolean('has_cost_warning')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
