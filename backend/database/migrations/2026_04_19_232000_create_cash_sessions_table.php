<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->integer('opening_amount');
            $table->string('status')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->integer('expected_cash_amount')->nullable();
            $table->integer('counted_cash_amount')->nullable();
            $table->integer('expected_transfer_amount')->nullable();
            $table->integer('difference_amount')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
