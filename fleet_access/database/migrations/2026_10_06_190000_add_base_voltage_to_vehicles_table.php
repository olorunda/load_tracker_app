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
            $table->unsignedInteger('base_voltage')->nullable()->after('status')->comment('Baseline/Initial external voltage in mV (IO 66) when empty');
            $table->unsignedInteger('current_voltage')->nullable()->after('base_voltage')->comment('Latest external voltage in mV (IO 66)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['base_voltage', 'current_voltage']);
        });
    }
};
