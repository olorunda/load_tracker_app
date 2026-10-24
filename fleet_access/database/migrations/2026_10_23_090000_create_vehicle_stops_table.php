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
        Schema::create('vehicle_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->unsignedBigInteger('start_position_id');
            $table->unsignedBigInteger('end_position_id');
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at');
            $table->unsignedInteger('duration_seconds');
            $table->double('latitude', 10, 7);
            $table->double('longitude', 10, 7);
            $table->boolean('ignition_off')->default(false);
            $table->unsignedInteger('load_before')->nullable()->comment('Median IO 66 mV while moving before the stop');
            $table->unsignedInteger('load_after')->nullable()->comment('Median IO 66 mV while moving after the stop');
            $table->integer('load_delta')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'start_position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_stops');
    }
};
