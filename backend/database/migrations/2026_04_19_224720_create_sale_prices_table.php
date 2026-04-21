<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_presentation_id')->constrained()->cascadeOnDelete();
            $table->integer('price_amount');
            $table->integer('min_price_amount')->nullable();
            $table->decimal('suggested_margin_percent', 8, 2)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_prices');
    }
};
