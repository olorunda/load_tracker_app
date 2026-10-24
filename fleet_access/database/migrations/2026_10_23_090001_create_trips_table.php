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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('start_stop_id')->constrained('vehicle_stops')->onDelete('cascade');
            $table->foreignId('end_stop_id')->nullable()->constrained('vehicle_stops')->onDelete('set null');
            $table->string('status')->default('pending')->index(); // pending, active, closing, completed, cancelled
            $table->unsignedInteger('baseline_load')->comment('Median IO 66 mV after loading');
            $table->timestamp('loaded_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unloaded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->unsignedBigInteger('distance_m')->default(0);
            $table->boolean('is_billable')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
