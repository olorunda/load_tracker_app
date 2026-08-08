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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('geofence_id')->nullable()->constrained('geofences')->onDelete('set null');
            $table->string('type'); // entry, exit, overspeed, diagnostic
            $table->string('severity')->default('warning'); // critical, warning, info
            $table->string('title');
            $table->text('description');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('triggered_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
