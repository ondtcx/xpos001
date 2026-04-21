<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();
            $table->string('origin_type');
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->timestamp('received_at');
            $table->date('expiration_date')->nullable();
            $table->decimal('initial_quantity', 12, 3);
            $table->decimal('available_quantity', 12, 3);
            $table->decimal('bonus_quantity', 12, 3)->default(0);
            $table->integer('unit_cost_final_amount');
            $table->integer('suggested_sale_price_amount')->nullable();
            $table->boolean('is_estimated')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};
