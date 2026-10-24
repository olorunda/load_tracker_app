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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('load_increase_percentage', 5, 2)
                ->default(0.00)
                ->after('base_voltage')
                ->comment('Percentage increase over base voltage required to trigger Loaded status (0.00 = any increase)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('load_increase_percentage');
        });
    }
};
