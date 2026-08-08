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
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('Depot Yard'); // Depot Yard, Port Terminal, Distribution Center, Highway Corridor
            $table->double('latitude', 10, 7);
            $table->double('longitude', 10, 7);
            $table->integer('radius_meters')->default(2500);
            $table->integer('max_speed_kmh')->default(40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
