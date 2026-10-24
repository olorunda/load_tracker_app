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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // load_status, overspeed, geofence, voltage, diagnostic, custom
            $table->text('description')->nullable();
            $table->string('target_group')->default('All Fleet Assets');
            $table->string('severity')->default('warning'); // critical, warning, info
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('icon')->default('notifications');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
