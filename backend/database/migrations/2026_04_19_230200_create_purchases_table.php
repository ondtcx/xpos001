<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->timestamp('purchased_at');
            $table->string('payment_type')->default('cash');
            $table->boolean('is_credit')->default(false);
            $table->integer('subtotal_amount')->default(0);
            $table->integer('global_discount_amount')->default(0);
            $table->integer('global_tax_amount')->default(0);
            $table->integer('extra_costs_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
