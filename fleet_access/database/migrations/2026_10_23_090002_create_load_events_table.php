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
        Schema::create('load_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            $table->foreignId('stop_id')->nullable()->constrained('vehicle_stops')->onDelete('cascade');
            $table->foreignId('alert_id')->nullable()->constrained('alerts')->onDelete('set null');
            // loading, unloading, suspected_unauthorized_loading, confirmed_unauthorized_loading,
            // unauthorized_loading_dismissed, unauthorized_offload
            $table->string('type')->index();
            $table->timestamp('occurred_at')->index();
            $table->double('latitude', 10, 7)->nullable();
            $table->double('longitude', 10, 7)->nullable();
            $table->unsignedInteger('load_value')->nullable();
            $table->integer('load_delta')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('load_events');
    }
};
