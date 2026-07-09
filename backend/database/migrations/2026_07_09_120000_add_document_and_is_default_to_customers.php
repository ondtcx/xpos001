<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('document', 50)->nullable()->after('name');
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['is_default']);
            $table->dropColumn(['document', 'is_default']);
        });
    }
};
