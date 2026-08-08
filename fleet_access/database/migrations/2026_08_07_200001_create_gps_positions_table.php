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
        Schema::create('gps_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('imei');
            $table->timestamp('recorded_at')->index();
            $table->double('latitude', 10, 7);
            $table->double('longitude', 10, 7);
            $table->integer('altitude_m')->default(0);
            $table->integer('angle_deg')->default(0);
            $table->integer('satellites')->default(0);
            $table->integer('speed_kmh')->default(0);
            $table->boolean('is_valid')->default(true);
            $table->boolean('ignition_state')->default(false);
            $table->boolean('movement_state')->default(false);
            $table->integer('internal_battery_voltage')->default(0); // mV
            $table->integer('external_voltage')->default(0); // mV
            $table->bigInteger('total_odometer')->default(0); // meters
            $table->integer('hdop')->default(0); // scaled_x10
            $table->string('codec')->default('Codec 8 Extended');
            $table->json('raw_io_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_positions');
    }
};
